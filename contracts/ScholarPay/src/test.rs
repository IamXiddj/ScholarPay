#[cfg(test)]
mod tests {
    use soroban_sdk::{
        testutils::{Address as _, MockAuth, MockAuthInvoke},
        token, Address, Env, IntoVal, String,
    };

    use crate::{ScholarPayContract, ScholarPayContractClient};

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /// Deploy a minimal SEP-41 token contract for use in tests.
    fn create_token<'a>(
        env: &Env,
        admin: &Address,
    ) -> (Address, token::StellarAssetClient<'a>) {
        let contract_address = env.register_stellar_asset_contract(admin.clone());
        let client = token::StellarAssetClient::new(env, &contract_address);
        (contract_address, client)
    }

    /// Deploy the ScholarPay contract, initialize it, and return (client, admin).
    fn setup(env: &Env) -> (ScholarPayContractClient, Address) {
        let contract_id = env.register_contract(None, ScholarPayContract);
        let client = ScholarPayContractClient::new(env, &contract_id);
        let admin = Address::generate(env);
        client.initialize(&admin);
        (client, admin)
    }

    // -----------------------------------------------------------------------
    // Test 1 — Happy Path: full MVP disbursement executes end-to-end
    // -----------------------------------------------------------------------
    #[test]
    fn test_disburse_happy_path() {
        let env = Env::default();
        env.mock_all_auths();

        let (client, admin) = setup(&env);
        let (token_address, token_client) = create_token(&env, &admin);

        // Fund the contract with 1000 USDC (7-decimal: 1000_0000000)
        let contract_address = client.address.clone();
        token_client.mint(&contract_address, &1_000_0000000);

        // Create scholarship
        let scholarship_id = client.create_scholarship(
            &admin,
            &String::from_str(&env, "STEM Global Grant 2025"),
            &1_000_0000000,
            &token_address,
        );

        // Disburse 200 USDC to a student
        let student = Address::generate(&env);
        let disburse_amount = 200_0000000_i128;
        let disbursement_id = client.disburse(
            &admin,
            &scholarship_id,
            &student,
            &disburse_amount,
            &String::from_str(&env, "tuition"),
        );

        // Verify student received the funds
        let token_read = token::Client::new(&env, &token_address);
        assert_eq!(token_read.balance(&student), disburse_amount);

        // Verify disbursement record was saved
        let record = client.get_disbursement(&disbursement_id);
        assert_eq!(record.student, student);
        assert_eq!(record.amount, disburse_amount);
        assert_eq!(record.scholarship_id, scholarship_id);
    }

    // -----------------------------------------------------------------------
    // Test 2 — Edge Case: disburse fails when called by non-admin
    // -----------------------------------------------------------------------
    #[test]
    #[should_panic(expected = "unauthorized")]
    fn test_disburse_unauthorized_caller() {
        let env = Env::default();
        env.mock_all_auths();

        let (client, admin) = setup(&env);
        let (token_address, token_client) = create_token(&env, &admin);

        token_client.mint(&client.address, &500_0000000);

        client.create_scholarship(
            &admin,
            &String::from_str(&env, "Women in Tech Fund"),
            &500_0000000,
            &token_address,
        );

        // An attacker (not the admin) tries to disburse
        let attacker = Address::generate(&env);
        let student = Address::generate(&env);

        client.disburse(
            &attacker, // ← not admin, should panic
            &0,
            &student,
            &100_0000000,
            &String::from_str(&env, "tuition"),
        );
    }

    // -----------------------------------------------------------------------
    // Test 3 — Edge Case: disburse fails when scholarship has insufficient funds
    // -----------------------------------------------------------------------
    #[test]
    #[should_panic(expected = "insufficient scholarship funds")]
    fn test_disburse_exceeds_remaining_balance() {
        let env = Env::default();
        env.mock_all_auths();

        let (client, admin) = setup(&env);
        let (token_address, token_client) = create_token(&env, &admin);

        // Scholarship total = 300 USDC
        token_client.mint(&client.address, &300_0000000);

        client.create_scholarship(
            &admin,
            &String::from_str(&env, "Micro-Grant Pilot"),
            &300_0000000,
            &token_address,
        );

        let student = Address::generate(&env);

        // First disbursement: 200 USDC — OK
        client.disburse(
            &admin,
            &0,
            &student,
            &200_0000000,
            &String::from_str(&env, "tuition"),
        );

        // Second disbursement: 200 USDC — should fail (only 100 left)
        client.disburse(
            &admin,
            &0,
            &student,
            &200_0000000,
            &String::from_str(&env, "books"),
        );
    }

    // -----------------------------------------------------------------------
    // Test 4 — State Verification: contract storage reflects correct state
    //           after a disbursement (disbursed counter, record fields)
    // -----------------------------------------------------------------------
    #[test]
    fn test_state_after_disbursement() {
        let env = Env::default();
        env.mock_all_auths();

        let (client, admin) = setup(&env);
        let (token_address, token_client) = create_token(&env, &admin);

        let total = 1_000_0000000_i128;
        token_client.mint(&client.address, &total);

        let scholarship_id = client.create_scholarship(
            &admin,
            &String::from_str(&env, "Global Access Fund"),
            &total,
            &token_address,
        );

        let student = Address::generate(&env);
        let amount = 350_0000000_i128;

        client.disburse(
            &admin,
            &scholarship_id,
            &student,
            &amount,
            &String::from_str(&env, "living"),
        );

        // Scholarship disbursed counter must be updated
        let scholarship = client.get_scholarship(&scholarship_id);
        assert_eq!(scholarship.disbursed, amount);
        assert_eq!(scholarship.total_amount - scholarship.disbursed, 650_0000000);
        assert!(scholarship.active);

        // Student disbursements list must contain the record
        let records = client.get_student_disbursements(&student);
        assert_eq!(records.len(), 1);
        let record = records.get(0).unwrap();
        assert_eq!(record.amount, amount);
        assert_eq!(record.purpose, String::from_str(&env, "living"));
    }

    // -----------------------------------------------------------------------
    // Test 5 — Edge Case: deactivated scholarship blocks further disbursements
    // -----------------------------------------------------------------------
    #[test]
    #[should_panic(expected = "scholarship is not active")]
    fn test_disburse_on_inactive_scholarship() {
        let env = Env::default();
        env.mock_all_auths();

        let (client, admin) = setup(&env);
        let (token_address, token_client) = create_token(&env, &admin);

        token_client.mint(&client.address, &500_0000000);

        let scholarship_id = client.create_scholarship(
            &admin,
            &String::from_str(&env, "Closed Pilot Grant"),
            &500_0000000,
            &token_address,
        );

        // Admin deactivates the scholarship
        client.deactivate_scholarship(&admin, &scholarship_id);

        // Disbursement must fail
        let student = Address::generate(&env);
        client.disburse(
            &admin,
            &scholarship_id,
            &student,
            &100_0000000,
            &String::from_str(&env, "tuition"),
        );
    }
}
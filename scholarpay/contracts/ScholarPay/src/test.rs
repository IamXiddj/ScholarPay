#![cfg(test)]
use super::*;
use soroban_sdk::{
    testutils::{Address as _, AuthorizedFunction, AuthorizedInvocation},
    token, Address, Env, IntoVal, String,
};

fn create_token_contract<'a>(
    env: &Env,
    admin: &Address,
) -> (token::Client<'a>, token::StellarAssetClient<'a>) {
    let contract_address = env.register_stellar_asset_contract(admin.clone());
    (
        token::Client::new(env, &contract_address),
        token::StellarAssetClient::new(env, &contract_address),
    )
}

#[test]
fn test_full_scholarship_flow() {
    let env = Env::default();
    env.mock_all_auths();

    let admin = Address::generate(&env);
    let student = Address::generate(&env);

    // Deploy USDC mock token
    let (usdc_client, usdc_admin_client) = create_token_contract(&env, &admin);
    usdc_admin_client.mint(&admin, &10_000_0000000); // 10,000 USDC (7 decimals)

    // Deploy ScholarPay contract
    let contract_id = env.register_contract(None, ScholarPayContract);
    let client = ScholarPayContractClient::new(&env, &contract_id);

    // Initialize
    client.initialize(&admin, &usdc_client.address);

    // Register grant for $500 USDC
    let grant_id = client.register_grant(
        &admin,
        &student,
        &500_0000000i128, // 500 USDC with 7 decimal places
        &String::from_str(&env, "Amara Osei"),
        &String::from_str(&env, "University of Lagos - Computer Science Year 2"),
    );

    assert_eq!(grant_id, 1);

    let grant = client.get_grant(&grant_id);
    assert_eq!(grant.status, GrantStatus::Registered);

    // AI Eligibility Verification step
    client.verify_eligibility(
        &admin,
        &grant_id,
        &String::from_str(&env, "sha256:abc123def456...eligibility_verified"),
    );

    let grant = client.get_grant(&grant_id);
    assert_eq!(grant.status, GrantStatus::EligibilityVerified);

    // Check balances before disbursement
    let admin_balance_before = usdc_client.balance(&admin);
    let student_balance_before = usdc_client.balance(&student);

    // Disburse!
    client.disburse(&admin, &grant_id);

    // Verify balances after disbursement
    let admin_balance_after = usdc_client.balance(&admin);
    let student_balance_after = usdc_client.balance(&student);

    assert_eq!(admin_balance_after, admin_balance_before - 500_0000000);
    assert_eq!(student_balance_after, student_balance_before + 500_0000000);

    let grant = client.get_grant(&grant_id);
    assert_eq!(grant.status, GrantStatus::Disbursed);
    assert!(grant.disbursed_at > 0);

    println!("✅ Full scholarship flow test passed!");
    println!("   Student received: {} USDC", student_balance_after / 10_000_000);
}

#[test]
#[should_panic(expected = "Grant must be eligibility-verified before disbursement")]
fn test_cannot_disburse_without_verification() {
    let env = Env::default();
    env.mock_all_auths();

    let admin = Address::generate(&env);
    let student = Address::generate(&env);
    let (usdc_client, usdc_admin_client) = create_token_contract(&env, &admin);
    usdc_admin_client.mint(&admin, &10_000_0000000);

    let contract_id = env.register_contract(None, ScholarPayContract);
    let client = ScholarPayContractClient::new(&env, &contract_id);

    client.initialize(&admin, &usdc_client.address);

    let grant_id = client.register_grant(
        &admin,
        &student,
        &500_0000000i128,
        &String::from_str(&env, "Test Student"),
        &String::from_str(&env, "Test University"),
    );

    // This should panic — no verification step
    client.disburse(&admin, &grant_id);
}

#[test]
fn test_revoke_grant() {
    let env = Env::default();
    env.mock_all_auths();

    let admin = Address::generate(&env);
    let student = Address::generate(&env);
    let (usdc_client, usdc_admin_client) = create_token_contract(&env, &admin);
    usdc_admin_client.mint(&admin, &10_000_0000000);

    let contract_id = env.register_contract(None, ScholarPayContract);
    let client = ScholarPayContractClient::new(&env, &contract_id);

    client.initialize(&admin, &usdc_client.address);

    let grant_id = client.register_grant(
        &admin,
        &student,
        &500_0000000i128,
        &String::from_str(&env, "Test Student"),
        &String::from_str(&env, "Test University"),
    );

    client.revoke_grant(&admin, &grant_id);

    let grant = client.get_grant(&grant_id);
    assert_eq!(grant.status, GrantStatus::Revoked);
    println!("✅ Grant revocation test passed!");
}
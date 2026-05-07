#![no_std]
use soroban_sdk::{
    contract, contractimpl, contracttype, token, Address, Env, String, Symbol, Vec, log,
};

// ─── Storage Keys ─────────────────────────────────────────────────────────────
#[contracttype]
#[derive(Clone)]
pub enum DataKey {
    Grant(u64),          // Grant storage by ID
    GrantCounter,        // Auto-increment grant ID
    Admin,               // NGO admin address
    UsdcToken,           // USDC token contract address
}

// ─── Grant Status ─────────────────────────────────────────────────────────────
#[contracttype]
#[derive(Clone, PartialEq, Debug)]
pub enum GrantStatus {
    Registered,
    EligibilityVerified,
    Disbursed,
    Revoked,
}

// ─── Grant Record ─────────────────────────────────────────────────────────────
#[contracttype]
#[derive(Clone)]
pub struct Grant {
    pub id: u64,
    pub ngo_admin: Address,
    pub recipient: Address,
    pub amount_usdc: i128,        // in stroops (7 decimals)
    pub student_name: String,
    pub purpose: String,
    pub status: GrantStatus,
    pub ai_verification_hash: String, // SHA256 of AI eligibility report
    pub disbursed_at: u64,            // ledger timestamp
    pub created_at: u64,
}

// ─── Contract ─────────────────────────────────────────────────────────────────
#[contract]
pub struct ScholarPayContract;

#[contractimpl]
impl ScholarPayContract {

    /// Initialize the contract with admin and USDC token address
    pub fn initialize(env: Env, admin: Address, usdc_token: Address) {
        if env.storage().instance().has(&DataKey::Admin) {
            panic!("Already initialized");
        }
        env.storage().instance().set(&DataKey::Admin, &admin);
        env.storage().instance().set(&DataKey::UsdcToken, &usdc_token);
        env.storage().instance().set(&DataKey::GrantCounter, &0u64);
    }

    /// NGO admin registers a new scholarship grant on-chain
    pub fn register_grant(
        env: Env,
        ngo_admin: Address,
        recipient: Address,
        amount_usdc: i128,
        student_name: String,
        purpose: String,
    ) -> u64 {
        ngo_admin.require_auth();

        let admin: Address = env.storage().instance().get(&DataKey::Admin).unwrap();
        if ngo_admin != admin {
            panic!("Only the NGO admin can register grants");
        }

        let id: u64 = env.storage().instance().get(&DataKey::GrantCounter).unwrap_or(0);
        let new_id = id + 1;

        let grant = Grant {
            id: new_id,
            ngo_admin: ngo_admin.clone(),
            recipient: recipient.clone(),
            amount_usdc,
            student_name,
            purpose,
            status: GrantStatus::Registered,
            ai_verification_hash: String::from_str(&env, ""),
            disbursed_at: 0,
            created_at: env.ledger().timestamp(),
        };

        env.storage().instance().set(&DataKey::Grant(new_id), &grant);
        env.storage().instance().set(&DataKey::GrantCounter, &new_id);

        log!(&env, "Grant {} registered for {}", new_id, recipient);
        new_id
    }

    /// Store AI eligibility verification result on-chain
    pub fn verify_eligibility(
        env: Env,
        ngo_admin: Address,
        grant_id: u64,
        ai_verification_hash: String,
    ) {
        ngo_admin.require_auth();

        let admin: Address = env.storage().instance().get(&DataKey::Admin).unwrap();
        if ngo_admin != admin {
            panic!("Only the NGO admin can verify eligibility");
        }

        let mut grant: Grant = env
            .storage()
            .instance()
            .get(&DataKey::Grant(grant_id))
            .expect("Grant not found");

        if grant.status != GrantStatus::Registered {
            panic!("Grant is not in Registered status");
        }

        grant.status = GrantStatus::EligibilityVerified;
        grant.ai_verification_hash = ai_verification_hash;

        env.storage().instance().set(&DataKey::Grant(grant_id), &grant);
        log!(&env, "Grant {} eligibility verified", grant_id);
    }

    /// Disburse USDC to the student's Stellar wallet
    /// This is the core transfer function — validates, transfers, records
    pub fn disburse(env: Env, ngo_admin: Address, grant_id: u64) {
        ngo_admin.require_auth();

        let admin: Address = env.storage().instance().get(&DataKey::Admin).unwrap();
        if ngo_admin != admin {
            panic!("Only the NGO admin can disburse funds");
        }

        let mut grant: Grant = env
            .storage()
            .instance()
            .get(&DataKey::Grant(grant_id))
            .expect("Grant not found");

        // Guard: must be verified before disbursement
        if grant.status != GrantStatus::EligibilityVerified {
            panic!("Grant must be eligibility-verified before disbursement");
        }

        let usdc_token: Address = env
            .storage()
            .instance()
            .get(&DataKey::UsdcToken)
            .unwrap();

        // Execute USDC token transfer via SEP-41 token interface
        let token_client = token::Client::new(&env, &usdc_token);

        // Transfer from NGO admin wallet → student wallet
        token_client.transfer(
            &ngo_admin,
            &grant.recipient,
            &grant.amount_usdc,
        );

        // Update grant status to disbursed — immutable audit trail
        grant.status = GrantStatus::Disbursed;
        grant.disbursed_at = env.ledger().timestamp();

        env.storage().instance().set(&DataKey::Grant(grant_id), &grant);

        // Emit event for indexers and Stellar Explorer
        env.events().publish(
            (Symbol::new(&env, "disbursed"), grant_id),
            (grant.recipient.clone(), grant.amount_usdc, grant.disbursed_at),
        );

        log!(&env, "Grant {} disbursed {} USDC to {}", grant_id, grant.amount_usdc, grant.recipient);
    }

    /// Admin can revoke a grant before disbursement
    pub fn revoke_grant(env: Env, ngo_admin: Address, grant_id: u64) {
        ngo_admin.require_auth();

        let admin: Address = env.storage().instance().get(&DataKey::Admin).unwrap();
        if ngo_admin != admin {
            panic!("Only the NGO admin can revoke grants");
        }

        let mut grant: Grant = env
            .storage()
            .instance()
            .get(&DataKey::Grant(grant_id))
            .expect("Grant not found");

        if grant.status == GrantStatus::Disbursed {
            panic!("Cannot revoke an already-disbursed grant");
        }

        grant.status = GrantStatus::Revoked;
        env.storage().instance().set(&DataKey::Grant(grant_id), &grant);
        log!(&env, "Grant {} revoked", grant_id);
    }

    // ─── View Functions ────────────────────────────────────────────────────────

    pub fn get_grant(env: Env, grant_id: u64) -> Grant {
        env.storage()
            .instance()
            .get(&DataKey::Grant(grant_id))
            .expect("Grant not found")
    }

    pub fn get_grant_count(env: Env) -> u64 {
        env.storage().instance().get(&DataKey::GrantCounter).unwrap_or(0)
    }

    pub fn get_admin(env: Env) -> Address {
        env.storage().instance().get(&DataKey::Admin).unwrap()
    }
}
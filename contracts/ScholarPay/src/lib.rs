#![no_std]

use soroban_sdk::{
    contract, contractimpl, contracttype, symbol_short,
    Address, Env, Map, String, Vec,
};

// ---------------------------------------------------------------------------
// Storage Keys
// ---------------------------------------------------------------------------

/// Top-level storage key for all scholarship records
const SCHOLARSHIPS: &str = "scholarships";
/// Top-level storage key for all disbursement records
const DISBURSEMENTS: &str = "disbursements";
/// Admin address — the NGO / institution that created the contract
const ADMIN: &str = "admin";

// ---------------------------------------------------------------------------
// Data Types
// ---------------------------------------------------------------------------

/// Represents a scholarship grant registered on-chain by an admin.
#[contracttype]
#[derive(Clone)]
pub struct Scholarship {
    /// Human-readable name, e.g. "STEM Global Grant 2025"
    pub name: String,
    /// Total amount (in stroops or USDC micro-units) available in this grant
    pub total_amount: i128,
    /// Amount already disbursed so far
    pub disbursed: i128,
    /// Stellar address of the token (USDC asset contract on Stellar)
    pub token: Address,
    /// Whether the scholarship is still accepting applications / disbursements
    pub active: bool,
}

/// Represents a single disbursement to one student.
#[contracttype]
#[derive(Clone)]
pub struct Disbursement {
    /// The scholarship ID this disbursement belongs to
    pub scholarship_id: u32,
    /// Student wallet address
    pub student: Address,
    /// Amount disbursed (in micro-units matching the token)
    pub amount: i128,
    /// Simple purpose tag, e.g. "tuition", "books", "living"
    pub purpose: String,
    /// Ledger sequence number when this was created (audit trail)
    pub ledger: u32,
}

// ---------------------------------------------------------------------------
// Contract
// ---------------------------------------------------------------------------

#[contract]
pub struct ScholarPayContract;

#[contractimpl]
impl ScholarPayContract {
    // -----------------------------------------------------------------------
    // Admin / Setup
    // -----------------------------------------------------------------------

    /// Initialize the contract with an admin address.
    /// Must be called once immediately after deployment.
    pub fn initialize(env: Env, admin: Address) {
        // Prevent re-initialization
        if env.storage().instance().has(&symbol_short!("admin")) {
            panic!("already initialized");
        }
        env.storage()
            .instance()
            .set(&symbol_short!("admin"), &admin);
    }

    // -----------------------------------------------------------------------
    // Scholarship Management
    // -----------------------------------------------------------------------

    /// Register a new scholarship on-chain.
    /// Only the admin (NGO / institution) may call this.
    ///
    /// Returns the newly created scholarship ID.
    pub fn create_scholarship(
        env: Env,
        caller: Address,
        name: String,
        total_amount: i128,
        token: Address,
    ) -> u32 {
        // Auth: only the admin may create scholarships
        caller.require_auth();
        Self::require_admin(&env, &caller);

        let mut scholarships = Self::load_scholarships(&env);
        let id = scholarships.len();

        let scholarship = Scholarship {
            name,
            total_amount,
            disbursed: 0,
            token,
            active: true,
        };

        scholarships.set(id, scholarship);
        env.storage()
            .instance()
            .set(&symbol_short!("schlrshps"), &scholarships);

        id
    }

    /// Deactivate a scholarship — prevents further disbursements.
    /// Only the admin may call this.
    pub fn deactivate_scholarship(env: Env, caller: Address, scholarship_id: u32) {
        caller.require_auth();
        Self::require_admin(&env, &caller);

        let mut scholarships = Self::load_scholarships(&env);
        let mut s = scholarships.get(scholarship_id).expect("scholarship not found");
        s.active = false;
        scholarships.set(scholarship_id, s);
        env.storage()
            .instance()
            .set(&symbol_short!("schlrshps"), &scholarships);
    }

    // -----------------------------------------------------------------------
    // Disbursement (Core MVP Transaction)
    // -----------------------------------------------------------------------

    /// Disburse funds from a scholarship directly to a student's Stellar wallet.
    ///
    /// Flow:
    ///   Admin calls disburse() →
    ///   Contract validates scholarship is active & has remaining funds →
    ///   Token transfer executes on-chain via Soroban token interface →
    ///   Disbursement record saved for audit trail →
    ///   Returns disbursement ID
    ///
    /// Why Stellar: near-zero fees, 3–5 second finality, USDC native on Stellar,
    /// and the audit log is immutable — no intermediary bank needed.
    pub fn disburse(
        env: Env,
        caller: Address,
        scholarship_id: u32,
        student: Address,
        amount: i128,
        purpose: String,
    ) -> u32 {
        // Auth: only the admin may disburse
        caller.require_auth();
        Self::require_admin(&env, &caller);

        // Validate amount
        if amount <= 0 {
            panic!("amount must be positive");
        }

        let mut scholarships = Self::load_scholarships(&env);
        let mut scholarship = scholarships
            .get(scholarship_id)
            .expect("scholarship not found");

        // Scholarship must be active
        if !scholarship.active {
            panic!("scholarship is not active");
        }

        // Check remaining balance
        let remaining = scholarship.total_amount - scholarship.disbursed;
        if amount > remaining {
            panic!("insufficient scholarship funds");
        }

        // --- On-chain token transfer via Soroban token interface ---
        // This calls the USDC (or any SEP-41 token) contract's transfer()
        // from the contract's own address to the student.
        let token_client = soroban_sdk::token::Client::new(&env, &scholarship.token);
        token_client.transfer(
            &env.current_contract_address(), // from: contract holds the funds
            &student,                         // to: student wallet
            &amount,
        );

        // Update disbursed amount
        scholarship.disbursed += amount;
        scholarships.set(scholarship_id, scholarship);
        env.storage()
            .instance()
            .set(&symbol_short!("schlrshps"), &scholarships);

        // Record disbursement for audit trail
        let mut disbursements = Self::load_disbursements(&env);
        let disbursement_id = disbursements.len();
        disbursements.set(
            disbursement_id,
            Disbursement {
                scholarship_id,
                student,
                amount,
                purpose,
                ledger: env.ledger().sequence(),
            },
        );
        env.storage()
            .instance()
            .set(&symbol_short!("disbursmts"), &disbursements);

        disbursement_id
    }

    // -----------------------------------------------------------------------
    // Read / Query
    // -----------------------------------------------------------------------

    /// Get a scholarship by ID.
    pub fn get_scholarship(env: Env, scholarship_id: u32) -> Scholarship {
        let scholarships = Self::load_scholarships(&env);
        scholarships.get(scholarship_id).expect("not found")
    }

    /// Get a disbursement record by ID.
    pub fn get_disbursement(env: Env, disbursement_id: u32) -> Disbursement {
        let disbursements = Self::load_disbursements(&env);
        disbursements.get(disbursement_id).expect("not found")
    }

    /// List all disbursements for a specific student (audit / student portal view).
    pub fn get_student_disbursements(env: Env, student: Address) -> Vec<Disbursement> {
        let disbursements = Self::load_disbursements(&env);
        let mut result = Vec::new(&env);
        for i in 0..disbursements.len() {
            let d = disbursements.get(i).unwrap();
            if d.student == student {
                result.push_back(d);
            }
        }
        result
    }

    // -----------------------------------------------------------------------
    // Internal Helpers
    // -----------------------------------------------------------------------

    fn require_admin(env: &Env, caller: &Address) {
        let admin: Address = env
            .storage()
            .instance()
            .get(&symbol_short!("admin"))
            .expect("not initialized");
        if *caller != admin {
            panic!("unauthorized: caller is not admin");
        }
    }

    fn load_scholarships(env: &Env) -> Map<u32, Scholarship> {
        env.storage()
            .instance()
            .get(&symbol_short!("schlrshps"))
            .unwrap_or_else(|| Map::new(env))
    }

    fn load_disbursements(env: &Env) -> Map<u32, Disbursement> {
        env.storage()
            .instance()
            .get(&symbol_short!("disbursmts"))
            .unwrap_or_else(|| Map::new(env))
    }
}

mod test;
# ScholarPay

> **On-chain scholarship disbursement — get funds to students in seconds, not months.**

---

## The Problem

A university student in Lagos, Nairobi, or Manila is awarded a $500 scholarship from a global NGO. The funds arrive — weeks late, eaten by wire fees, and blocked behind bank paperwork the student can't navigate. The NGO has no audit trail. The student misses enrollment.

## The Solution

ScholarPay is a Soroban smart contract on Stellar that lets any institution (NGO, university, DAO) register scholarship grants on-chain and disburse USDC directly to a student's Stellar wallet in under 5 seconds — with a permanent, auditable record of every payment.

**What the admin does:** Register a scholarship grant → whitelist a student wallet → call `disburse()`.  
**What happens on-chain:** Soroban executes a USDC token transfer from the contract to the student. The disbursement amount, purpose, student address, and ledger timestamp are stored immutably.  
**Why Stellar:** ~$0.0001 transaction fees, 3–5 second finality, USDC is a first-class asset, and Soroban smart contracts make the audit trail trustless — no intermediary bank or currency conversion needed.

---

## Stellar Features Used

| Feature | Usage |
|---|---|
| **USDC / Custom token** | Scholarship funds held and transferred as SEP-41 token |
| **Soroban smart contracts** | Scholarship registry, disbursement logic, audit trail |
| **Trustlines** | Student wallet must have a USDC trustline before receiving |
| **XLM** | Used for transaction fees |

---

## Target Users

| Role | Detail |
|---|---|
| **Admin (sender)** | NGO program officer, university financial aid office, DAO treasurer — anyone managing a scholarship fund |
| **Student (recipient)** | University or vocational school student globally; needs a Stellar wallet (Freighter, LOBSTR, etc.) |
| **Pain** | Wire delays, high remittance fees, zero audit transparency, bank account requirements |

---

## Core Feature — MVP Transaction Flow

```
Admin calls disburse(scholarship_id, student_address, amount, purpose)
    → Soroban validates: scholarship active, funds available, caller is admin
    → token.transfer(contract → student, amount)   [on-chain, ~3 sec]
    → Disbursement record saved: student, amount, purpose, ledger sequence
    → Student wallet balance increases immediately
```

**Demo script (< 2 min):**
1. Show empty student Stellar wallet
2. Admin calls `disburse` via CLI or simple web UI
3. Show student wallet balance updated on Stellar Explorer
4. Show `get_student_disbursements` returning the on-chain audit record

---

## Vision and Purpose

ScholarPay removes every rent-seeking intermediary between a donor's intent and a student's opportunity. No SWIFT codes. No bank accounts. No 3-week clearing windows. Just a Stellar address and a smart contract that enforces the rules.

The same contract works for:
- Emergency micro-grants to displaced students
- Milestone-gated scholarships (disburse only after grade verification)
- DAO-governed grant programs with community voting on recipients

---

## Prerequisites

- **Rust** `>= 1.74` — [install](https://rustup.rs/)
- **Soroban CLI** `>= 21.0.0`
  ```bash
  cargo install --locked soroban-cli --features opt
  ```
- **Stellar Testnet account** with funded XLM (use [Friendbot](https://friendbot.stellar.org/))
- **Node.js** `>= 18` (optional, for frontend demo)

---

## Build

```bash
# Clone the repo
git clone https://github.com/your-org/scholar-pay
cd scholar_pay

# Build the Wasm contract
soroban contract build

# The compiled output will be at:
# target/wasm32-unknown-unknown/release/scholar_pay.wasm
```

---

## Test

```bash
cargo test
```

Expected output:
```
running 5 tests
test tests::test_disburse_happy_path ... ok
test tests::test_disburse_unauthorized_caller ... ok
test tests::test_disburse_exceeds_remaining_balance ... ok
test tests::test_state_after_disbursement ... ok
test tests::test_disburse_on_inactive_scholarship ... ok

test result: ok. 5 passed; 0 failed
```

---

## Deploy to Testnet

```bash
# 1. Configure your testnet identity
soroban keys generate --global alice --network testnet
soroban keys fund alice --network testnet

# 2. Deploy the contract
soroban contract deploy \
  --wasm target/wasm32-unknown-unknown/release/scholar_pay.wasm \
  --source alice \
  --network testnet

# Note the CONTRACT_ID printed in the output

# 3. Initialize (set admin)
soroban contract invoke \
  --id <CONTRACT_ID> \
  --source alice \
  --network testnet \
  -- initialize \
  --admin <YOUR_STELLAR_ADDRESS>
```

---

## Sample CLI Invocations

### Create a Scholarship
```bash
soroban contract invoke \
  --id <CONTRACT_ID> \
  --source alice \
  --network testnet \
  -- create_scholarship \
  --caller <ADMIN_ADDRESS> \
  --name "STEM Global Grant 2025" \
  --total_amount 500000000 \
  --token <USDC_CONTRACT_ADDRESS>
```

### Disburse to a Student (Core MVP Function)
```bash
soroban contract invoke \
  --id <CONTRACT_ID> \
  --source alice \
  --network testnet \
  -- disburse \
  --caller <ADMIN_ADDRESS> \
  --scholarship_id 0 \
  --student <STUDENT_STELLAR_ADDRESS> \
  --amount 50000000 \
  --purpose "tuition"
```
> `50000000` = 50 USDC (7 decimal places)

### Check a Student's Disbursement History
```bash
soroban contract invoke \
  --id <CONTRACT_ID> \
  --source alice \
  --network testnet \
  -- get_student_disbursements \
  --student <STUDENT_STELLAR_ADDRESS>
```

---

## Timeline

| Phase | Deliverable |
|---|---|
| Day 1 | Contract written, local tests passing |
| Day 2 | Deployed to testnet, CLI demo working |
| Day 3 | Minimal web UI (admin panel + student view) |
| Day 4 | Demo polish, pitch deck |

---

## Why This Wins

ScholarPay targets a massive, underserved, globally relatable problem — educational funding delays — with a concrete Stellar solution that uses Soroban contracts, USDC transfers, and an immutable audit trail. Judges can see real money move to a real wallet address in under 5 seconds. The users are not hypothetical: every scholarship-granting NGO in the world is a potential customer.

**Bonus:** The same contract architecture supports AI-powered eligibility checks (GPT-based document verification before `disburse()` is called) and integration with Stellar anchors for local fiat off-ramp in any country.

---

## contracts

https://stellar.expert/explorer/testnet/tx/1b9a54974c38ddc0944e76180f966d4c94b1130f1c1ac635ec1316d147d3b7b0
https://lab.stellar.org/r/testnet/contract/CA2EY3LL6EI3ARMSRPSIUDG5K5YFS6T5367Z46OAP6FIHJFYDX5H6JUU

## License

MIT — see [LICENSE](./LICENSE)
# ScholarPay

> **On-chain scholarship disbursement — get funds to students in seconds, not months.**

---

## The Problem

A university student in Lagos, Nairobi, or Manila is awarded a $500 scholarship from a global NGO. The funds arrive — weeks late, eaten by wire fees, and blocked behind bank paperwork the student can't navigate. The NGO has no audit trail. The student misses enrollment.

## The Solution

ScholarPay is a full-stack web system backed by a Soroban smart contract on Stellar. Any institution (NGO, university, DAO) can register scholarship grants on-chain and disburse USDC directly to a student's Stellar wallet in under 5 seconds — with a permanent, auditable record of every payment.

**What the admin does:** Register a scholarship grant → whitelist a student wallet → click Disburse.
**What happens on-chain:** Soroban executes a USDC token transfer from the contract to the student. The disbursement amount, purpose, student address, and ledger timestamp are stored immutably.
**Why Stellar:** ~$0.0001 transaction fees, 3–5 second finality, USDC is a first-class asset, and Soroban smart contracts make the audit trail trustless — no intermediary bank or currency conversion needed.

---

## Live Deployment

| Resource | Link |
|---|---|
| **Live App** | `https://your-project.vercel.app` |
| **Stellar Contract** | [CA2EY3LL6EI3ARMSRPSIUDG5K5YFS6T5367Z46OAP6FIHJFYDX5H6JUU](https://lab.stellar.org/r/testnet/contract/CA2EY3LL6EI3ARMSRPSIUDG5K5YFS6T5367Z46OAP6FIHJFYDX5H6JUU) |
| **Explorer Tx** | [View on Stellar Expert](https://stellar.expert/explorer/testnet/tx/1b9a54974c38ddc0944e76180f966d4c94b1130f1c1ac635ec1316d147d3b7b0) |
| **Database** | Supabase (PostgreSQL) |

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
| **Admin (sender)** | NGO program officer, university financial aid office, DAO treasurer |
| **Student (recipient)** | University or vocational school student globally; needs a Stellar wallet (Freighter, LOBSTR, etc.) |
| **Pain** | Wire delays, high remittance fees, zero audit transparency, bank account requirements |

---

## Tech Stack

### XAMPP Version (Local Development)
| Layer | Technology |
|---|---|
| **Frontend** | HTML, CSS, JavaScript |
| **Backend** | PHP 8+ |
| **Database** | MySQL via phpMyAdmin |
| **Server** | Apache (XAMPP) |
| **Auth** | PHP Sessions |

### Vercel Version (Production Deployment)
| Layer | Technology |
|---|---|
| **Frontend** | HTML, CSS, JavaScript (ES Modules) |
| **Backend** | Node.js Serverless Functions (Vercel) |
| **Database** | Supabase (PostgreSQL) |
| **Auth** | JWT (JSON Web Tokens) via `jsonwebtoken` |
| **Password hashing** | `bcryptjs` |
| **Hosting** | Vercel |

---

## Project Structure

### XAMPP (Local)
```
scholarpay/
├── index.php                  # Login / landing page
├── logout.php                 # Session logout
│
├── admin/
│   ├── dashboard.php          # Admin overview & stats
│   ├── scholarships.php       # Create & manage scholarships
│   ├── disburse.php           # Core MVP — disburse USDC
│   ├── students.php           # Manage student accounts & wallets
│   ├── audit.php              # Full disbursement audit log
│   └── settings.php           # Admin profile & password
│
├── student/
│   ├── dashboard.php          # Student overview
│   ├── disbursements.php      # Payment history
│   └── wallet.php             # Stellar wallet management
│
├── includes/
│   ├── auth.php               # Session auth helpers
│   ├── db.php                 # PDO MySQL connection
│   └── layout.php             # Sidebar & page shell
│
├── assets/
│   ├── css/app.css            # Global stylesheet
│   └── js/app.js              # Frontend utilities
│
└── db/
    └── schema.sql             # MySQL database schema + seed data
```

### Vercel (Production)
```
scholarpay-vercel/
├── index.html                 # Login page
├── vercel.json                # Routing & headers config
├── package.json               # Node.js dependencies
│
├── admin/
│   ├── dashboard.html
│   ├── scholarships.html
│   ├── disburse.html
│   ├── students.html
│   ├── audit.html
│   └── settings.html
│
├── student/
│   ├── dashboard.html
│   ├── disbursements.html
│   └── wallet.html
│
├── api/                       # Vercel Serverless Functions
│   ├── login.js
│   ├── logout.js
│   ├── scholarships.js
│   ├── disburse.js            # Core MVP function
│   ├── students.js
│   ├── disbursements.js
│   └── stats.js
│
├── lib/                       # Shared backend helpers
│   ├── auth.js                # JWT sign/verify
│   └── supabase.js            # Supabase client
│
└── assets/
    ├── css/app.css            # Global stylesheet (same as XAMPP)
    └── js/
        ├── app.js             # API client, auth, formatters
        └── layout.js          # Sidebar renderer
```

---

## Core Feature — MVP Transaction Flow

```
Admin calls disburse(scholarship_id, student_address, amount, purpose)
    → API validates: scholarship active, funds available, caller is admin
    → token.transfer(contract → student, amount)   [on-chain, ~3–5 sec]
    → Disbursement record saved: student, amount, purpose, ledger sequence, tx hash
    → Student wallet balance increases immediately
```

**Demo script (< 2 min):**
1. Log in as Admin → go to Disburse
2. Select a scholarship, pick a student, enter amount and purpose
3. Click "Disburse on Stellar" — confirm the dialog
4. See the success banner with live Tx Hash and Ledger sequence
5. Click "View on Stellar Explorer" to see the on-chain record
6. Log in as Student → My Disbursements shows the payment instantly

---

## Demo Accounts

| Role | Email | Password |
|---|---|---|
| **Admin** | admin@scholarpay.org | Student@123 |
| **Student** | student@scholarpay.org | Student@123 |
| **Student** | juan.delacruz@scholarpay.org | Student@123 |
| **Student** | ana.reyes@scholarpay.org | Student@123 |
| **Student** | carlos.mendoza@scholarpay.org | Student@123 |
| **Student** | sofia.ramos@scholarpay.org | Student@123 |
| **Student** | miguel.torres@scholarpay.org | Student@123 |
| **Student** | isabella.flores@scholarpay.org | Student@123 |
| **Student** | rafael.santos@scholarpay.org | Student@123 |
| **Student** | chloe.villanueva@scholarpay.org | Student@123 |
| **Student** | daniel.garcia@scholarpay.org | Student@123 |
| **Student** | gabrielle.lim@scholarpay.org | Student@123 |

---

## Local Setup (XAMPP)

### Prerequisites
- **XAMPP** with Apache + MySQL running
- PHP `>= 8.0`
- A browser

### Steps

```bash
# 1. Clone or copy the project into your htdocs folder
C:\xampp\htdocs\scholarpay\

# 2. Start Apache and MySQL in XAMPP Control Panel

# 3. Open phpMyAdmin
http://localhost/phpmyadmin

# 4. Create a database named: scholarpay

# 5. Import the schema
# Go to: scholarpay database → SQL tab → paste db/schema.sql → Go

# 6. Open the app
http://localhost/scholarpay/
```

The default database credentials in `includes/db.php` are:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // Default XAMPP — empty password
define('DB_NAME', 'scholarpay');
```

---

## Production Deployment (Vercel + Supabase)

### Prerequisites
- [Vercel account](https://vercel.com) (free)
- [Supabase account](https://supabase.com) (free)
- [Node.js](https://nodejs.org) >= 18
- [Git](https://git-scm.com)

### Step 1 — Set up Supabase

1. Create a new project at [supabase.com](https://supabase.com)
2. Go to **SQL Editor** → paste and run the contents of `db/schema.sql`
3. Go to **Project Settings → API** and copy:
   - `Project URL` → this is your `SUPABASE_URL`
   - `service_role` secret key → this is your `SUPABASE_SERVICE_KEY`

### Step 2 — Deploy to Vercel

```bash
# Install Vercel CLI
npm install -g vercel

# Navigate to the vercel project
cd scholarpay-vercel

# Install dependencies
npm install

# Deploy
vercel

# Follow the prompts — link to your Vercel account and project
```

### Step 3 — Set Environment Variables

In your Vercel project dashboard → **Settings → Environment Variables**, add:

| Variable | Value |
|---|---|
| `SUPABASE_URL` | Your Supabase project URL |
| `SUPABASE_SERVICE_KEY` | Your Supabase service role key |
| `JWT_SECRET` | Any long random string (e.g. `openssl rand -hex 32`) |

### Step 4 — Redeploy

```bash
vercel --prod
```

Your app is now live at `https://your-project.vercel.app`.

---

## Database Schema

| Table | Purpose |
|---|---|
| `users` | Admins and students, with Stellar wallet addresses |
| `scholarships` | Grant pools with total and remaining USDC amounts |
| `disbursements` | Full audit trail: amount, purpose, tx hash, ledger sequence |
| `whitelisted_wallets` | Approved student Stellar addresses |
| `activity_log` | All admin and student actions with IP and timestamp |

---

## Smart Contract (Soroban)

### Prerequisites
- **Rust** `>= 1.74` — [install](https://rustup.rs/)
- **Soroban CLI** `>= 21.0.0`

```bash
cargo install --locked soroban-cli --features opt
```

### Build

```bash
git clone https://github.com/your-org/scholar-pay
cd scholar_pay
soroban contract build
# Output: target/wasm32-unknown-unknown/release/scholar_pay.wasm
```

### Test

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

### Deploy to Testnet

```bash
# 1. Configure your testnet identity
soroban keys generate --global alice --network testnet
soroban keys fund alice --network testnet

# 2. Deploy the contract
soroban contract deploy \
  --wasm target/wasm32-unknown-unknown/release/scholar_pay.wasm \
  --source alice \
  --network testnet

# 3. Initialize (set admin)
soroban contract invoke \
  --id <CONTRACT_ID> \
  --source alice \
  --network testnet \
  -- initialize \
  --admin <YOUR_STELLAR_ADDRESS>
```

### Sample CLI Invocations

**Create a Scholarship:**
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

**Disburse to a Student:**
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

**Check a Student's Disbursement History:**
```bash
soroban contract invoke \
  --id <CONTRACT_ID> \
  --source alice \
  --network testnet \
  -- get_student_disbursements \
  --student <STUDENT_STELLAR_ADDRESS>
```

---

## API Reference (Vercel)

All endpoints are under `/api/`. Authenticated endpoints require `Authorization: Bearer <token>` header.

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `POST` | `/api/login` | None | Login, returns JWT token |
| `POST` | `/api/logout` | User | Log activity and invalidate |
| `GET` | `/api/stats` | User | Dashboard stats |
| `GET` | `/api/scholarships` | User | List scholarships |
| `POST` | `/api/scholarships` | Admin | Create scholarship |
| `PATCH` | `/api/scholarships` | Admin | Update status |
| `POST` | `/api/disburse` | Admin | Disburse USDC to student |
| `GET` | `/api/disbursements` | User | Audit log (filtered) |
| `GET` | `/api/students` | Admin | List all students |
| `POST` | `/api/students` | Admin | Add new student |
| `PATCH` | `/api/students` | User | Update Stellar wallet |

---

## Setting Up a Student Stellar Wallet

Students need a Stellar wallet with a USDC trustline to receive disbursements.

**Option 1 — Freighter (Browser Extension)**
1. Install from [freighter.app](https://freighter.app)
2. Create wallet, switch to **Testnet** in Settings
3. Add USDC trustline via the Assets tab
4. Copy public key (starts with `G`) and submit to admin

**Option 2 — LOBSTR (Mobile)**
1. Download from [lobstr.co](https://lobstr.co)
2. Create account and search for USDC to add as an asset
3. Go to Settings → Wallet address and copy your public key

**Testnet USDC issuer:**
```
GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5
```

---

## Vision and Purpose

ScholarPay removes every rent-seeking intermediary between a donor's intent and a student's opportunity. No SWIFT codes. No bank accounts. No 3-week clearing windows. Just a Stellar address and a smart contract that enforces the rules.

The same contract works for:
- Emergency micro-grants to displaced students
- Milestone-gated scholarships (disburse only after grade verification)
- DAO-governed grant programs with community voting on recipients
- AI-powered eligibility checks (document verification before `disburse()` is called)
- Integration with Stellar anchors for local fiat off-ramp in any country

---

## Contract Links

| Resource | Link |
|---|---|
| Testnet Transaction | https://stellar.expert/explorer/testnet/tx/1b9a54974c38ddc0944e76180f966d4c94b1130f1c1ac635ec1316d147d3b7b0 |
| Contract on Stellar Lab | https://lab.stellar.org/r/testnet/contract/CA2EY3LL6EI3ARMSRPSIUDG5K5YFS6T5367Z46OAP6FIHJFYDX5H6JUU |

---

## License

MIT — see [LICENSE](./LICENSE)

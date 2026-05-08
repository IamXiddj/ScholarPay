// api/debug-login.js
// TEMPORARY DEBUG FILE — delete after fixing login
// Visit: POST https://your-app.vercel.app/api/debug-login
// with body: { "email": "admin@scholarpay.org", "password": "password" }

import { supabase } from '../lib/supabase.js';
import bcrypt from 'bcryptjs';

export default async function handler(req, res) {
  if (req.method === 'OPTIONS') return res.status(200).end();

  const { email, password } = req.body || {};
  const log = [];

  try {
    // Step 1: Check env vars
    log.push({
      step: '1_env_vars',
      SUPABASE_URL:        process.env.SUPABASE_URL ? 'SET ✓' : 'MISSING ✗',
      SUPABASE_SERVICE_KEY: process.env.SUPABASE_SERVICE_KEY ? 'SET ✓' : 'MISSING ✗',
      JWT_SECRET:          process.env.JWT_SECRET ? 'SET ✓' : 'MISSING ✗',
    });

    // Step 2: Try fetching user from Supabase
    const { data: user, error: fetchError } = await supabase
      .from('users')
      .select('id, name, email, role, password')
      .eq('email', (email || '').toLowerCase().trim())
      .single();

    log.push({
      step: '2_fetch_user',
      email_searched: (email || '').toLowerCase().trim(),
      user_found: !!user,
      supabase_error: fetchError?.message || null,
      hash_prefix: user ? user.password.slice(0, 7) : null,
      hash_length: user ? user.password.length : null,
    });

    if (!user) {
      return res.status(200).json({ log, verdict: 'FAIL — user not found in database' });
    }

    // Step 3: Test password comparison
    const raw       = user.password;
    const normalized = raw.replace(/^\$2a\$/, '$2b$').replace(/^\$2y\$/, '$2b$');

    const matchRaw        = await bcrypt.compare(password || '', raw);
    const matchNormalized = await bcrypt.compare(password || '', normalized);

    log.push({
      step: '3_password_check',
      password_received:   password ? `"${password}" (${password.length} chars)` : 'EMPTY',
      hash_prefix_raw:     raw.slice(0, 7),
      hash_prefix_after:   normalized.slice(0, 7),
      match_raw:           matchRaw,
      match_normalized:    matchNormalized,
    });

    // Step 4: Try generating a fresh hash and compare
    const freshHash  = await bcrypt.hash(password || '', 10);
    const freshMatch = await bcrypt.compare(password || '', freshHash);

    log.push({
      step: '4_fresh_hash_sanity',
      fresh_hash_prefix: freshHash.slice(0, 7),
      fresh_match:       freshMatch,
      bcryptjs_working:  freshMatch ? 'YES ✓' : 'NO ✗',
    });

    const verdict = matchNormalized
      ? 'SUCCESS — password matches, login should work'
      : 'FAIL — password does not match stored hash';

    return res.status(200).json({ log, verdict });

  } catch (err) {
    return res.status(500).json({ log, error: err.message, stack: err.stack });
  }
}

// api/settings.js
import { supabase } from '../lib/supabase.js';
import { requireAdmin } from '../lib/auth.js';
import bcrypt from 'bcryptjs';

export default async function handler(req, res) {
  if (req.method === 'OPTIONS') return res.status(200).end();
  if (req.method !== 'PATCH') return res.status(405).json({ error: 'Method not allowed' });

  const user = requireAdmin(req, res);
  if (!user) return;

  const { action } = req.body || {};

  // ── Update Profile ──────────────────────────────────────────
  if (action === 'update_profile') {
    const { name, email, institution } = req.body;
    if (!name || !email)
      return res.status(400).json({ error: 'Name and email are required.' });

    // Check email not taken by another user
    const { data: existing } = await supabase
      .from('users')
      .select('id')
      .eq('email', email.toLowerCase())
      .neq('id', user.id)
      .single();

    if (existing)
      return res.status(409).json({ error: 'That email is already in use by another account.' });

    const { error } = await supabase
      .from('users')
      .update({ name, email: email.toLowerCase(), institution: institution || null })
      .eq('id', user.id);

    if (error) return res.status(500).json({ error: error.message });

    await supabase.from('activity_log').insert({
      user_id: user.id,
      action: 'UPDATE_PROFILE',
      description: `Admin updated profile: ${name}`
    });

    return res.status(200).json({ success: true });
  }

  // ── Change Password ─────────────────────────────────────────
  if (action === 'change_password') {
    const { current_password, new_password } = req.body;
    if (!current_password || !new_password)
      return res.status(400).json({ error: 'Current and new password are required.' });
    if (new_password.length < 6)
      return res.status(400).json({ error: 'New password must be at least 6 characters.' });

    // Fetch current hash
    const { data: dbUser, error: fetchErr } = await supabase
      .from('users')
      .select('password')
      .eq('id', user.id)
      .single();

    if (fetchErr || !dbUser)
      return res.status(404).json({ error: 'User not found.' });

    const valid = await bcrypt.compare(current_password, dbUser.password);
    if (!valid)
      return res.status(401).json({ error: 'Current password is incorrect.' });

    const hashed = await bcrypt.hash(new_password, 10);
    const { error } = await supabase
      .from('users')
      .update({ password: hashed })
      .eq('id', user.id);

    if (error) return res.status(500).json({ error: error.message });

    await supabase.from('activity_log').insert({
      user_id: user.id,
      action: 'CHANGE_PASSWORD',
      description: 'Admin changed password'
    });

    return res.status(200).json({ success: true });
  }

  return res.status(400).json({ error: 'Unknown action.' });
}

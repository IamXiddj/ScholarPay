// api/reset-demo-passwords.js
// ONE-TIME USE ONLY — delete this file after running it
// Visit: https://your-app.vercel.app/api/reset-demo-passwords
// to reset all demo account passwords

import bcrypt from 'bcryptjs';
import { supabase } from '../lib/supabase.js';

export default async function handler(req, res) {
  if (req.method === 'OPTIONS') return res.status(200).end();

  // Generate fresh hashes using bcryptjs (same library your login uses)
  const adminHash   = await bcrypt.hash('password', 10);
  const studentHash = await bcrypt.hash('Student@123', 10);

  // Update admin
  const { error: adminErr } = await supabase
    .from('users')
    .update({ password: adminHash })
    .eq('email', 'admin@scholarpay.org');

  // Update all students
  const { error: studentErr } = await supabase
    .from('users')
    .update({ password: studentHash })
    .eq('role', 'student');

  if (adminErr || studentErr) {
    return res.status(500).json({
      error: 'Update failed',
      adminErr,
      studentErr
    });
  }

  // Verify by fetching and testing
  const { data: adminUser } = await supabase
    .from('users')
    .select('name, email, password')
    .eq('email', 'admin@scholarpay.org')
    .single();

  const adminOk = adminUser
    ? await bcrypt.compare('password', adminUser.password)
    : false;

  return res.status(200).json({
    success: true,
    message: 'All demo passwords have been reset.',
    results: {
      admin_login_works:   adminOk,
      admin_hash_prefix:   adminHash.slice(0, 7),
      student_hash_prefix: studentHash.slice(0, 7),
    },
    accounts: [
      { email: 'admin@scholarpay.org',           password: 'password'    },
      { email: 'student@scholarpay.org',          password: 'Student@123' },
      { email: 'juan.delacruz@scholarpay.org',   password: 'Student@123' },
      { email: 'ana.reyes@scholarpay.org',        password: 'Student@123' },
      { email: 'carlos.mendoza@scholarpay.org',   password: 'Student@123' },
      { email: 'sofia.ramos@scholarpay.org',      password: 'Student@123' },
      { email: 'miguel.torres@scholarpay.org',    password: 'Student@123' },
      { email: 'isabella.flores@scholarpay.org',  password: 'Student@123' },
      { email: 'rafael.santos@scholarpay.org',    password: 'Student@123' },
      { email: 'chloe.villanueva@scholarpay.org', password: 'Student@123' },
      { email: 'daniel.garcia@scholarpay.org',    password: 'Student@123' },
      { email: 'gabrielle.lim@scholarpay.org',    password: 'Student@123' },
    ]
  });
}

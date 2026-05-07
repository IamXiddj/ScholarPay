// lib/auth.js — JWT helpers for API routes
import jwt from 'jsonwebtoken';

const JWT_SECRET = process.env.JWT_SECRET || 'scholarpay-secret-change-in-production';

export function signToken(payload) {
    return jwt.sign(payload, JWT_SECRET, { expiresIn: '8h' });
}

export function verifyToken(token) {
    try {
        return jwt.verify(token, JWT_SECRET);
    } catch {
        return null;
    }
}

// Extract and verify token from Authorization header
export function getUser(req) {
    const auth = req.headers['authorization'] || '';
    const token = auth.startsWith('Bearer ') ? auth.slice(7) : null;
    if (!token) return null;
    return verifyToken(token);
}

// Middleware helper — returns user or sends 401
export function requireAuth(req, res) {
    const user = getUser(req);
    if (!user) {
        res.status(401).json({ error: 'Unauthorized. Please log in.' });
        return null;
    }
    return user;
}

export function requireAdmin(req, res) {
    const user = requireAuth(req, res);
    if (!user) return null;
    if (user.role !== 'admin') {
        res.status(403).json({ error: 'Admin access required.' });
        return null;
    }
    return user;
}

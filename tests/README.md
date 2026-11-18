# Testing Guide - Umojawetu Platform

This directory contains comprehensive test suites for testing the security fixes and API endpoints.

## Test Files

### 1. Security Tests (`security-tests.php`)
**Location:** `/tests/security-tests.php`

Tests core security functions:
- ✅ CORS header configuration
- ✅ Input validation & sanitization
- ✅ Email validation
- ✅ Integer/Float validation
- ✅ Required field validation
- ✅ Admin credential verification
- ✅ CSRF token generation & verification
- ✅ Session timeout detection
- ✅ Database connection

**How to run:**
1. Open browser: `http://localhost/umojawetu-platform/tests/security-tests.php`
2. View results in browser (visual dashboard)

**Expected Result:** All tests should PASS (green checkmarks)

---

### 2. API Tests (`api-tests.php`)
**Location:** `/tests/api-tests.php`

Tests API endpoint validation:
- ✅ Pledge creation (missing fields, invalid email, valid request)
- ✅ Payment intent (invalid amount, missing amount)
- ✅ Request submission (missing fields, invalid email)
- ✅ CORS headers presence

**How to run:**

**Via Browser:**
- All tests: `http://localhost/umojawetu-platform/tests/api-tests.php`
- Pledge tests only: `http://localhost/umojawetu-platform/tests/api-tests.php?test=pledge`
- Payment tests: `http://localhost/umojawetu-platform/tests/api-tests.php?test=payment`
- Request tests: `http://localhost/umojawetu-platform/tests/api-tests.php?test=request`
- CORS tests: `http://localhost/umojawetu-platform/tests/api-tests.php?test=cors`

**Via PowerShell (curl):**
```powershell
# Run all API tests
Invoke-WebRequest -Uri "http://localhost/umojawetu-platform/tests/api-tests.php" | ConvertFrom-Json

# Run pledge tests only
Invoke-WebRequest -Uri "http://localhost/umojawetu-platform/tests/api-tests.php?test=pledge" | ConvertFrom-Json

# Run payment tests
Invoke-WebRequest -Uri "http://localhost/umojawetu-platform/tests/api-tests.php?test=payment" | ConvertFrom-Json
```

**Expected Result:** 
- Invalid requests should return HTTP 400
- Valid requests should return HTTP 200/201
- CORS headers should be present

---

## Test Coverage Summary

### Security Tests (10 tests)
1. ✅ setCorsHeaders() function exists
2. ✅ sanitizeInput() function exists
3. ✅ validateRequired() function exists
4. ✅ HTML escaping works
5. ✅ Email validation works
6. ✅ Invalid email rejected
7. ✅ Integer validation works
8. ✅ Invalid integer rejected
9. ✅ Float validation works
10. ✅ Admin login verification works
11. ✅ Wrong credentials rejected
12. ✅ CSRF token generation
13. ✅ CSRF token validation
14. ✅ Session timeout detection
15. ✅ Environment detection
16. ✅ Database connection

### API Tests (8+ tests)
1. ✅ Pledge API - missing required fields (400 error)
2. ✅ Pledge API - valid request (200 success)
3. ✅ Pledge API - invalid email (400 error)
4. ✅ Payment API - invalid amount (400 error)
5. ✅ Payment API - missing amount (400 error)
6. ✅ Request API - missing fields (400 error)
7. ✅ Request API - invalid email (400 error)
8. ✅ CORS headers present

---

## Manual Testing

### Test Admin Login
1. Visit: `http://localhost/umojawetu-platform/admin/login.php`
2. Enter:
   - Username: `admin`
   - Password: `admin123`
3. Should redirect to dashboard

### Test Input Validation
```bash
# Test pledge API with missing field
curl -X POST http://localhost/umojawetu-platform/api/pledges/create-pledge.php \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com"}'
# Expected: 400 error with "Missing field" message

# Test with invalid email
curl -X POST http://localhost/umojawetu-platform/api/pledges/create-pledge.php \
  -H "Content-Type: application/json" \
  -d '{"pledger_name":"John","email":"invalid","amount_pledged":100}'
# Expected: 400 error with "Invalid email" message

# Test with valid data
curl -X POST http://localhost/umojawetu-platform/api/pledges/create-pledge.php \
  -H "Content-Type: application/json" \
  -d '{"pledger_name":"John Doe","email":"john@example.com","amount_pledged":100}'
# Expected: 200 success response
```

### Test CORS Headers
```bash
# Test CORS from localhost (allowed)
curl -i -X OPTIONS http://localhost/umojawetu-platform/api/pledges/get-pledges-public.php \
  -H "Origin: http://localhost"
# Should see: Access-Control-Allow-Origin: * (or specific origin in production)

# Test CORS from different origin (production)
curl -i -X OPTIONS http://localhost/umojawetu-platform/api/pledges/get-pledges-public.php \
  -H "Origin: http://malicious.com"
# Should NOT allow if APP_ENV=production
```

---

## Test Results Interpretation

### Green (✓ PASS)
- Test passed successfully
- Function works as expected
- No security vulnerabilities detected

### Red (✗ FAIL)
- Test failed
- Function needs fixing
- Check details for error information

### Pass Rate
- **100%** - All security implementations working correctly ✅
- **>90%** - Mostly working, minor issues
- **<90%** - Significant issues need fixing

---

## Continuous Testing

To run tests regularly:

1. **Daily:** Run `security-tests.php` to ensure core functions work
2. **Before Deployment:** Run both test suites
3. **After Updates:** Re-run all tests to verify changes
4. **CI/CD:** Integrate tests into your deployment pipeline

---

## Troubleshooting

### Tests Fail
1. Check the error message in test details
2. Verify config/security.php exists and is included
3. Verify config/.env has admin credentials
4. Check database connection is working

### API Tests Show Curl Errors
1. Ensure XAMPP/Apache is running
2. Verify proper base URL in test file
3. Check error logs in `/storage/logs/`

### Security Tests Return False
1. Check if security.php is properly required in config.php
2. Verify Environment class is working
3. Check PHP version compatibility

---

## Security Test Goals

After implementing security improvements, these tests verify:
- ✅ Input validation prevents XSS, injection attacks
- ✅ CORS properly restricts unauthorized origins
- ✅ Admin credentials aren't hardcoded
- ✅ Session timeouts work
- ✅ CSRF tokens protect forms
- ✅ Email validation works
- ✅ Type validation (int, float, etc) works
- ✅ Database connection is secure

---

## Next Steps

Once all tests pass:
1. Update production `.env` with real credentials
2. Change `APP_ENV=production`
3. Update CORS allowed origins
4. Run full application test scenario
5. Deploy with confidence! 🎉

---

**Test Status Link:** `/tests/security-tests.php` - Real-time health check

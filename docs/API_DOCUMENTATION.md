# API Documentation

This document provides comprehensive documentation for the BotMirzaPanel API endpoints and services.

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Error Handling](#error-handling)
4. [Rate Limiting](#rate-limiting)
5. [User Management API](#user-management-api)
6. [Payment API](#payment-api)
7. [Bot Management API](#bot-management-api)
8. [Data Transfer Objects](#data-transfer-objects)
9. [Response Formats](#response-formats)
10. [Examples](#examples)

## Overview

### Base URL
```
Production: https://api.botmirzapanel.com/v1
Development: http://localhost:8000/api/v1
```

### Content Type
All API requests and responses use JSON format:
```
Content-Type: application/json
Accept: application/json
```

### API Versioning
The API uses URL versioning. Current version is `v1`.

## Authentication

### Bearer Token Authentication
Most endpoints require authentication using Bearer tokens:

```http
Authorization: Bearer {your-api-token}
```

### API Key Authentication
Some endpoints support API key authentication:

```http
X-API-Key: {your-api-key}
```

### Authentication Endpoints

#### Login
```http
POST /auth/login
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "expires_at": "2024-12-31T23:59:59Z",
    "user": {
      "id": "user_123",
      "email": "user@example.com",
      "username": "johndoe"
    }
  }
}
```

#### Refresh Token
```http
POST /auth/refresh
```

#### Logout
```http
POST /auth/logout
```

## Error Handling

### Standard Error Response
All errors follow a consistent format:

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The provided data is invalid",
    "details": {
      "field": "email",
      "reason": "Invalid email format"
    },
    "timestamp": "2024-01-15T10:30:00Z",
    "request_id": "req_abc123"
  }
}
```

### HTTP Status Codes

| Code | Description | Usage |
|------|-------------|-------|
| 200 | OK | Successful GET, PUT, PATCH requests |
| 201 | Created | Successful POST requests |
| 204 | No Content | Successful DELETE requests |
| 400 | Bad Request | Invalid request data |
| 401 | Unauthorized | Authentication required |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource not found |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server errors |

### Error Codes

| Code | Description |
|------|-------------|
| `VALIDATION_ERROR` | Request validation failed |
| `AUTHENTICATION_ERROR` | Authentication failed |
| `AUTHORIZATION_ERROR` | Insufficient permissions |
| `RESOURCE_NOT_FOUND` | Requested resource not found |
| `RATE_LIMIT_EXCEEDED` | Too many requests |
| `SERVICE_UNAVAILABLE` | Service temporarily unavailable |
| `INTERNAL_ERROR` | Internal server error |

## Rate Limiting

### Limits
- **Authenticated users**: 1000 requests per hour
- **Unauthenticated users**: 100 requests per hour
- **Payment endpoints**: 100 requests per hour

### Headers
Rate limit information is included in response headers:

```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1640995200
```

## User Management API

### Get Current User
```http
GET /users/me
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "user_123",
    "username": "johndoe",
    "email": "john@example.com",
    "firstName": "John",
    "lastName": "Doe",
    "status": "active",
    "balance": 1500.00,
    "currency": "USD",
    "roles": ["user"],
    "isEmailVerified": true,
    "isPhoneVerified": false,
    "createdAt": "2024-01-01T00:00:00Z",
    "updatedAt": "2024-01-15T10:30:00Z"
  }
}
```

### Create User
```http
POST /users
```

**Request Body:**
```json
{
  "username": "johndoe",
  "email": "john@example.com",
  "password": "securePassword123",
  "firstName": "John",
  "lastName": "Doe",
  "phoneNumber": "+1234567890"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "user_123",
    "username": "johndoe",
    "email": "john@example.com",
    "firstName": "John",
    "lastName": "Doe",
    "status": "pending_verification",
    "createdAt": "2024-01-15T10:30:00Z"
  }
}
```

### Update User
```http
PUT /users/{userId}
```

### Delete User
```http
DELETE /users/{userId}
```

### Get User Balance
```http
GET /users/{userId}/balance
```

### Update User Balance
```http
PATCH /users/{userId}/balance
```

**Request Body:**
```json
{
  "amount": 100.00,
  "operation": "add",
  "description": "Bonus credit"
}
```

### Change User Status
```http
PATCH /users/{userId}/status
```

**Request Body:**
```json
{
  "status": "suspended",
  "reason": "Policy violation"
}
```

## Payment API

### Create Payment
```http
POST /payments
```

**Request Body:**
```json
{
  "userId": "user_123",
  "amount": 50.00,
  "currency": "USD",
  "gateway": "stripe",
  "description": "Bot service subscription",
  "metadata": {
    "orderId": "order_456",
    "productId": "prod_789"
  }
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "payment_123",
    "userId": "user_123",
    "orderId": "order_456",
    "amount": 50.00,
    "currency": "USD",
    "gateway": "stripe",
    "status": "pending",
    "description": "Bot service subscription",
    "createdAt": "2024-01-15T10:30:00Z",
    "paymentUrl": "https://checkout.stripe.com/pay/cs_test_..."
  }
}
```

### Get Payment
```http
GET /payments/{paymentId}
```

### Get User Payments
```http
GET /users/{userId}/payments
```

**Query Parameters:**
- `status` (optional): Filter by payment status
- `gateway` (optional): Filter by payment gateway
- `limit` (optional): Number of results (default: 20, max: 100)
- `offset` (optional): Pagination offset (default: 0)
- `startDate` (optional): Filter payments from date (ISO 8601)
- `endDate` (optional): Filter payments to date (ISO 8601)

**Response:**
```json
{
  "success": true,
  "data": {
    "payments": [
      {
        "id": "payment_123",
        "amount": 50.00,
        "currency": "USD",
        "status": "completed",
        "gateway": "stripe",
        "createdAt": "2024-01-15T10:30:00Z"
      }
    ],
    "pagination": {
      "total": 150,
      "limit": 20,
      "offset": 0,
      "hasMore": true
    }
  }
}
```

### Complete Payment
```http
POST /payments/{paymentId}/complete
```

### Refund Payment
```http
POST /payments/{paymentId}/refund
```

**Request Body:**
```json
{
  "amount": 25.00,
  "reason": "Customer request"
}
```

### Cancel Payment
```http
POST /payments/{paymentId}/cancel
```

## Bot Management API

### Get Bots
```http
GET /bots
```

### Create Bot
```http
POST /bots
```

### Update Bot
```http
PUT /bots/{botId}
```

### Delete Bot
```http
DELETE /bots/{botId}
```

### Start Bot
```http
POST /bots/{botId}/start
```

### Stop Bot
```http
POST /bots/{botId}/stop
```

## Data Transfer Objects

### UserDTO
```json
{
  "id": "string",
  "username": "string",
  "email": "string",
  "firstName": "string",
  "lastName": "string",
  "phoneNumber": "string|null",
  "status": "active|inactive|suspended|pending_verification",
  "balance": "number",
  "currency": "string",
  "roles": ["string"],
  "isEmailVerified": "boolean",
  "isPhoneVerified": "boolean",
  "lastLoginAt": "string|null",
  "createdAt": "string",
  "updatedAt": "string"
}
```

### PaymentDTO
```json
{
  "id": "string",
  "userId": "string",
  "orderId": "string|null",
  "amount": "number",
  "currency": "string",
  "gateway": "string",
  "status": "pending|completed|failed|cancelled|refunded|expired",
  "transactionId": "string|null",
  "description": "string|null",
  "metadata": "object|null",
  "gatewayResponse": "object|null",
  "failureReason": "string|null",
  "createdAt": "string",
  "updatedAt": "string",
  "completedAt": "string|null",
  "refundedAt": "string|null"
}
```

## Response Formats

### Success Response
```json
{
  "success": true,
  "data": {
    // Response data
  },
  "meta": {
    // Optional metadata
    "timestamp": "2024-01-15T10:30:00Z",
    "version": "1.0.0"
  }
}
```

### Error Response
```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable error message",
    "details": {
      // Additional error details
    },
    "timestamp": "2024-01-15T10:30:00Z",
    "request_id": "req_abc123"
  }
}
```

### Paginated Response
```json
{
  "success": true,
  "data": {
    "items": [
      // Array of items
    ],
    "pagination": {
      "total": 150,
      "limit": 20,
      "offset": 0,
      "hasMore": true,
      "nextOffset": 20
    }
  }
}
```

## Examples

### Complete User Registration Flow

1. **Create User**
```bash
curl -X POST https://api.botmirzapanel.com/v1/users \
  -H "Content-Type: application/json" \
  -d '{
    "username": "johndoe",
    "email": "john@example.com",
    "password": "securePassword123",
    "firstName": "John",
    "lastName": "Doe"
  }'
```

2. **Verify Email** (User clicks verification link)

3. **Login**
```bash
curl -X POST https://api.botmirzapanel.com/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "securePassword123"
  }'
```

### Payment Processing Flow

1. **Create Payment**
```bash
curl -X POST https://api.botmirzapanel.com/v1/payments \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": "user_123",
    "amount": 50.00,
    "currency": "USD",
    "gateway": "stripe",
    "description": "Bot service subscription"
  }'
```

2. **Redirect User to Payment URL**

3. **Handle Webhook** (Payment gateway notifies completion)

4. **Verify Payment Status**
```bash
curl -X GET https://api.botmirzapanel.com/v1/payments/payment_123 \
  -H "Authorization: Bearer {token}"
```

### Error Handling Example

```javascript
// JavaScript example
async function createUser(userData) {
  try {
    const response = await fetch('/api/v1/users', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify(userData)
    });

    const result = await response.json();

    if (!result.success) {
      // Handle API error
      console.error('API Error:', result.error.message);
      
      if (result.error.code === 'VALIDATION_ERROR') {
        // Handle validation errors
        console.error('Validation failed:', result.error.details);
      }
      
      throw new Error(result.error.message);
    }

    return result.data;
  } catch (error) {
    // Handle network or other errors
    console.error('Request failed:', error.message);
    throw error;
  }
}
```

## SDK and Client Libraries

### Official SDKs
- **PHP SDK**: `composer require botmirzapanel/php-sdk`
- **JavaScript SDK**: `npm install @botmirzapanel/js-sdk`
- **Python SDK**: `pip install botmirzapanel-sdk`

### Community SDKs
- **Go SDK**: Available on GitHub
- **Ruby SDK**: Available on GitHub

## Webhooks

### Webhook Events
- `user.created`
- `user.updated`
- `user.deleted`
- `payment.created`
- `payment.completed`
- `payment.failed`
- `payment.refunded`
- `bot.started`
- `bot.stopped`

### Webhook Payload Example
```json
{
  "event": "payment.completed",
  "data": {
    "id": "payment_123",
    "userId": "user_123",
    "amount": 50.00,
    "currency": "USD",
    "status": "completed",
    "completedAt": "2024-01-15T10:30:00Z"
  },
  "timestamp": "2024-01-15T10:30:00Z",
  "signature": "sha256=..."
}
```

## Support

For API support and questions:
- **Documentation**: https://docs.botmirzapanel.com
- **Support Email**: api-support@botmirzapanel.com
- **Discord**: https://discord.gg/botmirzapanel
- **GitHub Issues**: https://github.com/botmirzapanel/api/issues

## Changelog

### v1.0.0 (2024-01-15)
- Initial API release
- User management endpoints
- Payment processing endpoints
- Bot management endpoints
- Authentication and authorization
- Rate limiting implementation
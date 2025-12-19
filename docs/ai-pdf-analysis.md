# AI-Powered PDF Bill Analysis

## Overview

When a user uploads a utility bill PDF, the system automatically analyzes its contents using Google's **Gemini 2.5 Flash** model and generates a structured summary. This summary is stored alongside the bill record and displayed in the UI, giving users instant insight into their bills without manually reading each document.

## Architecture

```
User uploads PDF
       │
       ▼
┌──────────────────────┐
│  FileUploadController │
│                      │
│  1. Validate PDF     │
│  2. Analyze (raw)  ──┼──► GeminiPdfAnalyzerService
│  3. Encrypt PDF      │         │
│  4. Store to disk    │         ▼
│  5. Save bill record │    Gemini API (v1beta)
│     + bill_summary   │    gemini-2.5-flash
└──────────────────────┘
```

**Key design decision:** The PDF is analyzed _before_ encryption. Once encrypted, the raw content is no longer accessible, so Gemini receives the original unencrypted bytes via base64-encoded inline data.

## Components

### Database

| Column | Type | Description |
|--------|------|-------------|
| `bill_summary` | `TEXT`, nullable | AI-generated summary of the uploaded bill PDF |

Migration: `database/migrations/2026_03_19_062258_add_bill_summary_to_bills_table.php`

### Backend

#### `GeminiPdfAnalyzerService` (`app/Services/GeminiPdfAnalyzerService.php`)

Responsible for communicating with the Gemini API. Key behaviors:

- Reads the raw PDF file and base64-encodes it
- Sends it to `gemini-2.5-flash` via the `gemini-api-php/client` SDK using `v1beta` API version
- Uses a structured prompt requesting: company name, billing period, account number, total amount, due date, and key line items
- Returns the summary text, or `null` on any failure
- **Graceful degradation:** All errors are caught and logged. A failed analysis never blocks the upload

#### `FileUploadController` (`app/Http/Controllers/FileUploadController.php`)

Modified to inject `GeminiPdfAnalyzerService`. On upload:

1. Validates the PDF
2. If `doc_type === 'bill'`, calls `GeminiPdfAnalyzerService::analyze()` with the raw file path
3. Encrypts the PDF (existing behavior)
4. Stores the file (existing behavior)
5. Saves `bill_summary` on the bill record
6. Returns `bill_summary` in the JSON response

#### `BillController` (`app/Http/Controllers/BillController.php`)

The `getBillsOfCurrentUser()` SQL query now includes `b.bill_summary` so the summary is available in bill listings.

### Frontend

#### `Bill.ts` (`resources/js/Data/Bill.ts`)

Added `bill_summary?: string` to both `Bill` and `Bill_Plain_Obj` types, plus both conversion functions.

#### `Dashboard.vue` (`resources/js/Pages/Dashboard.vue`)

- `onBillUploaded()` handler parses the upload XHR response to extract `bill_summary`
- A read-only `Textarea` with an AI sparkle icon (✨) displays the summary in the edit bill dialog when available

## Configuration

Add the following to `.env`:

```env
GEMINI_API_KEY=your-api-key-here
```

The key is read via `config/services.php` → `services.gemini.api_key`.

## Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| `gemini-api-php/client` | ^1.7 | PHP SDK for the Gemini API |
| `guzzlehttp/guzzle` | (existing) | PSR-18 HTTP client used by the SDK |

## Testing

```bash
php artisan test --filter=GeminiPdfAnalyzerTest
```

Integration test at `tests/Feature/GeminiPdfAnalyzerTest.php`:

- Sends `tests/fixtures/Sample_Utility_Bill.pdf` to the real Gemini API
- Asserts a non-empty, meaningful summary is returned
- Automatically skipped if `GEMINI_API_KEY` is not configured

## Error Handling

| Scenario | Behavior |
|----------|----------|
| Missing `GEMINI_API_KEY` | Warning logged, `bill_summary` set to `null`, upload proceeds normally |
| Gemini API error (rate limit, invalid key, etc.) | Error logged, `bill_summary` set to `null`, upload proceeds normally |
| PDF file unreadable | Error logged, `bill_summary` set to `null`, upload proceeds normally |

The feature is designed to **never block** a bill upload regardless of AI service availability.

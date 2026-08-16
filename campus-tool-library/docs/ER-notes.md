# Database Design Notes

## Entities and relationships

- **users (1) — (many) items** — one user can own many items (`items.owner_id`
  is a foreign key to `users.id`). An item has exactly one owner.
- **users (1) — (many) borrow_requests** — one user can submit many borrow
  requests (`borrow_requests.borrower_id`). A request has exactly one
  borrower.
- **items (1) — (many) borrow_requests** — one item can receive many borrow
  requests over time (`borrow_requests.item_id`), but only one can be
  `approved` at a time (enforced in application logic in
  `request_action.php`, which auto-rejects competing pending requests once
  one is approved).
- **categories (1) — (many) items** — a fixed lookup table so filtering/
  browsing stays consistent instead of relying on free-text tags.
- **borrow_requests (1) — (0..1) impact_log** — when a loan is marked
  "returned," one impact_log row is written, used to power the "items
  saved / money saved" counters. This keeps the sustainability analytics
  separate from the transactional borrow_requests table.

## Normalization

The schema is in **3rd Normal Form (3NF)**:

- Every non-key column depends only on the table's primary key (no
  transitive dependencies) — e.g. `item_condition` and `photo_path`
  describe the item itself, not the owner, so they live in `items`, not
  duplicated into `borrow_requests`.
- Repeating groups are avoided: instead of storing multiple category tags
  as a comma-separated string on `items`, categories are a separate table
  referenced by foreign key.
- `borrow_requests.status` is an `ENUM` rather than a free-text column to
  keep the state machine (`pending → approved → returned`, or
  `pending → rejected`/`cancelled`) constrained at the database level.

## Why a `status` column on both `items` and `borrow_requests`?

They track two different things:
- `items.status` — is the physical item currently available to *anyone*?
- `borrow_requests.status` — what happened to *this specific* request?

Keeping them separate lets the history of every request stay intact (for
the reputation/impact features) even after an item cycles back to
`available` for its next borrower.

## Indexes

`idx_items_status`, `idx_items_category`, and `idx_requests_status` speed
up the two most common queries: browsing available items by category, and
loading a user's pending/approved requests on the dashboard.

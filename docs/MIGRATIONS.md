# Phase 1 migration notes

## 006_dashboard_queries_and_message_reads.php

Adds per-participant consultation read positions and indexes for unread messages, cash summaries and public listing queries. No existing transaction history or review status is rewritten. Existing messages initially count as unread until the participant marks the displayed messages read.

Apply through `php bin/migrate.php` with the migration account. The migration checks index existence and can be safely retried. Test on a staging copy first.

Rollback: deploy the previous application revision before removing the new structures. Back up the read-position table if those preferences must be retained. Drop `CONSULTATION_READ`; drop `idx_chat_unread` on `CONSULTATION_MESSAGE`, `idx_payee_collection` and `idx_verified_date` on `PAYMENT`, `idx_public_listing` on `PRODUCT`, and `idx_public_tour` on `FARM_TOUR`. Remove only the corresponding `SCHEMA_MIGRATION` version row if deliberately preparing to reapply. Do not remove business tables or edit migrations 001–005. Rolling back loses read preferences, not messages or transactions.

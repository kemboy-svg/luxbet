# Real-money launch gate

LuxBet is a **demo-only** platform. Real-money gambling, deposits,
withdrawals, wagering and cash-equivalent rewards are disabled and must stay
disabled until every legal, regulatory, game-provider, banking and payment
approval listed in `instructions.md` §2 has been independently verified and
formally recorded. Software configuration, a passing sandbox test or an
administrator's preference is never enough.

## How the gate is enforced

The gate fails closed. It reports "enabled" only when **every** condition
holds; anything missing, malformed, expired, unreadable or inconsistent keeps
it disabled. In this release four independent locks keep it off:

| Layer | Where | What it does |
| --- | --- | --- |
| Configuration | `config/luxbet.php` → `real_money.enabled` | A literal `false`. Not read from the environment, so there is no `.env` bypass. |
| Database | `launch_control_real_money_locked` check constraint | PostgreSQL refuses any row with `real_money_enabled = true`. |
| Model | `App\Domain\Compliance\Models\LaunchControl` | Saving with `real_money_enabled = true`, or deleting the record, throws `RealMoneyLockedException`. Nothing is mass assignable. |
| Workflow | `LaunchGate::REASON_MULTI_PERSON_WORKFLOW_MISSING` | The multi-person approval workflow does not exist yet, so the gate always has at least this blocking reason. |

`App\Domain\Compliance\LaunchGate\LaunchGate` is the single enforcement
point. Every backend action that could ever touch real money must check it,
normally through the `real-money` route middleware
(`App\Http\Middleware\EnsureRealMoneyEnabled`), which returns
`403 REAL_MONEY_DISABLED` while the gate is closed. The frontend is never the
enforcement layer; the `luxbet.realMoneyEnabled` Inertia prop is for display
only.

## Conditions checked (all must pass)

1. Configuration lock lifted (`config_locked`).
2. Multi-person approval workflow implemented (`multi_person_approval_workflow_not_implemented`).
3. Launch-control record present and readable (`record_missing`, `record_unreadable`).
4. Record flag on (`record_flag_off`).
5. Record and configured environment are both `production` and match (`environment_not_production`, `environment_mode_mismatch`).
6. At least one approved jurisdiction, each an ISO 3166-1 alpha-2 code (`no_approved_jurisdictions`, `invalid_approved_jurisdictions`).
7. Approval reference, approval document, approver, approval date not in the future (`approval_reference_missing`, `approval_document_missing`, `approver_missing`, `approved_at_missing`, `approved_at_in_future`).
8. Review date present and not passed (`review_due_at_missing`, `review_expired`).
9. Change reason recorded and version valid (`change_reason_missing`, `invalid_version`).

## Viewing the status

Status is read-only for every role:

```bash
php artisan luxbet:launch-gate
```

Staff with `launch_gate.view` (compliance officers, super admins) see the
same `LaunchGateStatus` read-only at `/admin/launch-control`. There is no command, route, setting or seed that changes it, and
tests assert that no write route to launch control exists.

## What lifting the gate would require

Not part of this project's current scope. At minimum: written legal sign-off
recorded per §2, a separately approved project change, a new migration
removing the database lock, a reviewed change to `config/luxbet.php`, an
audited multi-person approval workflow, and the separate regulated modules
listed in §5.2 (cash ledger, payments, KYC/AML, responsible-gambling
controls). The development team must not do this on its own initiative.

## Tests

`tests/Feature/Compliance/LaunchGateTest.php` and
`tests/Unit/Compliance/LaunchGateEvaluationTest.php` cover §11.6 items 1–6, 9
and 10. Items 7 and 8 (provider endpoints and credentials) arrive with the
provider layer in Milestone 5.

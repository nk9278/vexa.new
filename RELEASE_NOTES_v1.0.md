
# VEXA v1.0 Release Notes

## Overview
VEXA v1.0 establishes a robust foundation for modern digital agencies to manage their clients, projects, managers, employees, and finances in a securely partitioned, tenant-isolated SaaS environment.

## Features Finalized in v1.0
- **Workflow 1 & 2:** Super Admin mapping and automated, secure provisioning of Agency Owners and Managers complete with forced temporary password resets and forgot-password lifecycles.
- **Workflow 3 & 4:** Full CRM, Client, and Project deployment lifecycles mapped down to specific employees with localized Indian Currency metrics (₹).
- **Workflow 5, 6 & 7:** Complex Employee task submissions tied directly to Manager project scoping and CRM iterative review constraints.
- **Workflow 8:** Google Drive API OAuth token creation securely separated by agency for seamless media integration.
- **Workflow 9 & 10:** Auditing, live activity logs, notification broadcasts, and payment tracking modules completed safely and exported flawlessly using internal API streams.

## Fixes applied in final QA
- Prevented potential cross-tenant configuration leaks.
- Upgraded hardcoded local URL components to rely exclusively on `BASE_URL` logic for seamless deployment on standard subfolder setups (like Hostinger).
- Removed raw PHP errors from being visually printed over GUI templates in production mode (`APP_ENV`).
- Enforced hard 50MB and 10MB limits on media and document uploads to protect server architectures.

### Known Issues
- None.

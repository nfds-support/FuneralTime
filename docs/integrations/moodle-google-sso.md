# Moodle + OrangeHRM — Google Workspace SSO

Employees keep **one login** (Google Workspace). OrangeHRM and Moodle both authenticate against Google; email is the join key.

OrangeHRM does **not** act as an identity provider for Moodle. Its OAuth2 server is for API clients only.

## Prerequisites

- Google Workspace tenant with OAuth clients available
- OrangeHRM Starter with OpenID Connect enabled
- Moodle 4.x+ (self-hosted or managed)
- Every employee has a matching **work email** in OrangeHRM PIM that matches their Google account

## 1. OrangeHRM — Google OIDC

1. In [Google Cloud Console](https://console.cloud.google.com/), create (or reuse) an OAuth 2.0 Client ID (Web application).
2. Add authorized redirect URI:
   - `https://<orangehrm-host>/openidauth/callback`  
     (confirm the path matches your OpenID redirect controller if customized)
3. In OrangeHRM: **Admin → Configuration → OpenID Connect → Add Provider**
   - Name: `Google`
   - Client ID / Client Secret: from Google
   - URL: Google OIDC discovery  
     `https://accounts.google.com/.well-known/openid-configuration`
4. Ensure employee records use the same work email as Google Workspace.
5. Employees use **Log in with Google** on `/auth/login`.

## 2. Moodle — Google OAuth 2

1. In the same Google Cloud project, add another OAuth client (or add Moodle’s redirect URI to the existing client):
   - Redirect URI: `https://<moodle-host>/admin/oauth2callback.php`
2. In Moodle: **Site administration → Server → OAuth 2 services → Google**
   - Enter Client ID and Client Secret
   - Confirm endpoints resolve via discovery
3. **Site administration → Plugins → Authentication → Manage authentication**
   - Enable **OAuth 2**
   - Prefer preventing open self-registration; provision users via the OrangeHRM → Moodle sync (see below) or allow create-on-first-login only for known emails
4. Map identity fields so **email** is the unique identifier (matches OrangeHRM work email).

## 3. Email join key

| System | Field |
|--------|--------|
| Google Workspace | primary email |
| OrangeHRM | Employee work email (`workEmail`) |
| Moodle | user `email` |

First login to either app creates/links the local account by email. Keep emails unique and current in PIM.

## 4. OrangeHRM Moodle integration settings

After installing OrangeHRM 5.9.5+ (Policy module):

1. **Policy → Learning / Moodle settings** (admin)
   - Moodle base URL (e.g. `https://learn.example.com`)
   - Moodle web service token (site admin token with user/cohort functions)
   - Enable sync
2. Map **job titles → Moodle cohort IDs** under the same section.
3. Register an OrangeHRM OAuth client only if an external sync worker will call OrangeHRM APIs; the built-in `orangehrm:moodle-sync-users` command runs inside OrangeHRM and calls Moodle.

## 5. Deep links

- Side menu **Policy → Learning** opens Moodle (SSO already satisfied via Google).
- Published policies may include a **Moodle course URL**; employees see **Open required course** next to acknowledgment.

## 6. Operator checklist

- [ ] Google OAuth clients for OrangeHRM and Moodle redirect URIs
- [ ] OrangeHRM OpenID provider configured and tested
- [ ] Moodle OAuth 2 Google service enabled
- [ ] Work emails aligned across Google / OrangeHRM / Moodle
- [ ] Moodle base URL + web service token saved in OrangeHRM
- [ ] Job title → cohort maps created
- [ ] `orangehrm:run-schedule` cron active (Moodle sync runs hourly when enabled)
- [ ] Manual test: `php bin/console orangehrm:moodle-sync-users`

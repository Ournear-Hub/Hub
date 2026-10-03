# CRM, channel partners, investors, campaigns

Admin-only growth/ops modules. Not candidate account features.

## CRM — IMPLEMENTED (Stage 7)

`CrmLead`, `CrmOpportunity`, `CrmController`. Convert opportunity → employer. Leads from employer/contact enquiries.

## Channel partners — IMPLEMENTED (Stage 8)

`ChannelPartner`, agreements, `EmployerAttribution`, commissions (`CommissionService`). **Not** the same as employer-client ATS submissions. No public partner portal (`UserRole::Partner` unused as login portal).

## Investors — IMPLEMENTED (Stage 9) as staff CRM

`Investor`, `InvestmentOpportunity`, `InvestorInterest`. Verify/suspend. No investor self-serve account.

## Campaigns / referrals — IMPLEMENTED (Stage 10)

`Campaign`, `ReferralLink`, `Referral`, `ShareEvent`. Cookie `CaptureReferralCode` (unencrypted cookie name). Attribution on register/apply.

## Tests

`CrmFoundationStage7Test`, `ChannelPartnerStage8Test`, `InvestorFoundationStage9Test`, `CampaignReferralStage10Test`, `ProductionUxStage20Test`.

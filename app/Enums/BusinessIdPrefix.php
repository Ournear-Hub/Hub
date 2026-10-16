<?php

namespace App\Enums;

enum BusinessIdPrefix: string
{
    case User = 'ONT-U';
    case Candidate = 'ONT-C';
    case Job = 'ONT-J';
    case Application = 'ONT-A';
    case Employer = 'ONT-E';
    case Submission = 'ONT-S';
    case TalentPoolSubmission = 'ONT-TPS';
    case StaffingSubmission = 'ONT-SPS';
    case Placement = 'ONT-P';
    case Employee = 'ONT-W';
    case Replacement = 'ONT-R';
    case Invoice = 'ONT-I';
    case Payment = 'ONT-M';
    case Resume = 'ONT-V';
    case Document = 'ONT-D';
    case EmployerDocument = 'ONT-EDC';
    case RelationshipReview = 'ONT-REV';
    case Freelancer = 'ONT-F';
    case ChannelPartner = 'ONT-K';
    case PartnerAgreement = 'ONT-G';
    case CommissionEntry = 'ONT-N';
    case CommissionPayment = 'ONT-Q';
    case Investor = 'ONT-H';
    case InvestmentOpportunity = 'ONT-Y';
    case InvestorInterest = 'ONT-T';
    case Campaign = 'ONT-B';
    case ReferralLink = 'ONT-L';
    case ShareEvent = 'ONT-X';
    case Referral = 'ONT-Z';
}

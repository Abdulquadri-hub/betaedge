<?php

namespace App\Enums\Tenant;

enum StepStatus: string
{
    case PENDING = "pending";
    case PROCESSED = "processed";
    case PROCESSING = "processing";
    case COMPLETED = "completed";
    case VERYFING_PAYMENT = "veryfing_payment";
    case PROFILE = "profile";
    case INSTRUCTORS_INVITATION = "instructors_invitation";
    case ACADEMIC_STRUCTURE = "academic_structure";
    case COURSES = "courses";
    case SUBCRIPTION = "subscription";
    case PAYMENT = "payment";
    case ACTIVE = 'active';
    case FAILED = 'failed';
    case SUSPENDED = 'suspended';
    case ERROR = 'error';
}

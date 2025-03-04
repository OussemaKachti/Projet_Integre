<?php

namespace App\Enum;

enum GoalTypeEnum: string {
    case EVENT_COUNT = 'EVENT_COUNT';
    case EVENT_LIKES = 'EVENT_LIKES';
    case MEMBER_COUNT = 'MEMBER_COUNT';
}

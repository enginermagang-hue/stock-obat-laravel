<?php

namespace App\Enums;

enum MetodePrediksi: string
{
    case HoltWinters = 'holt_winters';
    case Prophet = 'prophet';
    case Xgboost = 'xgboost';
    case Historis = 'historis';
}

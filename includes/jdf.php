<?php
/**
 * JDF — Lightweight Pure PHP Jalali (Shamsi) Calendar Conversion Library
 * Compatible with PHP 8.x
 */

function jdate($format, $timestamp = 'none', $none = '', $time_zone = 'Asia/Tehran', $tr_num = 'fa') {
    $T_sec = 0;
    if ($time_zone != 'local') {
        date_default_timezone_set($time_zone);
    }
    $ts = ($timestamp === 'none') ? time() : count_filter($timestamp, false, false, true, true);
    $date = explode('_', date('Y_m_d_B_l_j_m_n_s_w_Y_z_W_a_A_g_G_h_H_i_s_u_O_P_T_U_Z_c_r_e_I_L_O_T_B', $ts));
    list($j_y, $j_m, $j_d) = gregorian_to_jalali($date[0], $date[1], $date[2]);
    $doy = ($j_m < 7) ? (($j_m - 1) * 31 + $j_d - 1) : (($j_m - 7) * 30 + $j_d + 185);
    $kab = (((($j_y % 33) % 4) - 1) == intval((($j_y % 33) * 0.05))) ? 1 : 0;
    $sl = strlen($format);
    $out = '';
    for ($i = 0; $i < $sl; $i++) {
        $sub = substr($format, $i, 1);
        if ($sub == '\\') {
            $out .= substr($format, ++$i, 1);
            continue;
        }
        switch ($sub) {
            case 'Y': $out .= $j_y; break;
            case 'y': $out .= substr($j_y, 2, 2); break;
            case 'm': $out .= ($j_m < 10) ? '0' . $j_m : $j_m; break;
            case 'n': $out .= $j_m; break;
            case 'd': $out .= ($j_d < 10) ? '0' . $j_d : $j_d; break;
            case 'j': $out .= $j_d; break;
            case 'F':
                $months = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
                $out .= $months[$j_m - 1];
                break;
            case 'M':
                $months = ['فرو', 'ارد', 'خرد', 'تیر', 'مرد', 'شهر', 'مهر', 'آبا', 'آذر', 'دی', 'بهم', 'اسف'];
                $out .= $months[$j_m - 1];
                break;
            case 'l':
                $days = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه'];
                $out .= $days[($date[9] + 1) % 7];
                break;
            case 'D':
                $days = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];
                $out .= $days[($date[9] + 1) % 7];
                break;
            case 'H': $out .= $date[18]; break;
            case 'i': $out .= $date[19]; break;
            case 's': $out .= $date[20]; break;
            default: $out .= $sub; break;
        }
    }
    if ($tr_num == 'fa') {
        $en = ['0','1','2','3','4','5','6','7','8','9'];
        $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $out = str_replace($en, $fa, $out);
    }
    return $out;
}

function gregorian_to_jalali($gy, $gm, $gd) {
    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + (int)(($gy2 + 3) / 4) - (int)(($gy2 + 99) / 100) + (int)(($gy2 + 399) / 400) + $gd + $g_d_m[$gm - 1];
    $jy = -1595 + (33 * (int)($days / 12053));
    $days %= 12053;
    $jy += 4 * (int)($days / 1461);
    $days %= 1461;
    if ($days > 365) {
        $jy += (int)(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    if ($days < 186) {
        $jm = 1 + (int)($days / 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + (int)(($days - 186) / 30);
        $jd = 1 + (($days - 186) % 30);
    }
    return [$jy, $jm, $jd];
}

function count_filter($num, $p2e = true, $e2p = false, $d2e = false, $e2d = false) {
    if (is_numeric($num)) return (int)$num;
    $ts = strtotime($num);
    return ($ts !== false) ? $ts : time();
}

/**
 * Convenient helper: Convert any datetime string/timestamp to human Shamsi date
 */
function to_shamsi($datetime, string $format = 'Y/m/d'): string {
    if (empty($datetime)) return '-';
    $ts = is_numeric($datetime) ? (int)$datetime : strtotime($datetime);
    if ($ts === false || $ts <= 0) return '-';
    return jdate($format, $ts);
}

/**
 * Convenient helper: Persian formatted date with time (e.g. ۱۵ شهریور ۱۴۰۴ ساعت ۱۸:۳۰)
 */
function human_shamsi($datetime): string {
    if (empty($datetime)) return '-';
    $ts = is_numeric($datetime) ? (int)$datetime : strtotime($datetime);
    if ($ts === false || $ts <= 0) return '-';
    return jdate('j F Y ساعت H:i', $ts);
}

<?php
/**
 * Public-facing English labels for roster positions (DB may store Spanish).
 */
declare(strict_types=1);

function vcf_roster_position_en(?string $posicion, ?string $sub_posicion): string
{
    static $main = [
        'Portero' => 'Goalkeeper',
        'Defensa' => 'Defense',
        'Mediocampista' => 'Midfield',
        'Delantero' => 'Forward',
    ];
    static $sub = [
        'Portero' => 'Goalkeeper',
        'Lateral izquierdo' => 'Left back',
        'Central izquierdo' => 'Left centre-back',
        'Central derecho' => 'Right centre-back',
        'Lateral derecho' => 'Right back',
        'Pivote' => 'Defensive midfielder',
        'Interior izquierdo' => 'Left central midfielder',
        'Interior derecho' => 'Right central midfielder',
        'Extremo izquierdo' => 'Left winger',
        'Delantero centro (9)' => 'Striker',
        'Extremo derecho' => 'Right winger',
    ];
    $pMain = $posicion ? ($main[$posicion] ?? $posicion) : '';
    $pSub = $sub_posicion ? ($sub[$sub_posicion] ?? $sub_posicion) : '';
    if ($pSub !== '') {
        return $pMain !== '' ? $pMain . ' · ' . $pSub : $pSub;
    }
    return $pMain;
}

/**
 * Short position label for roster cards (GK, CM, …).
 */
function vcf_roster_pos_short(array $j): string
{
    $sub = $j['sub_posicion'] ?? '';
    $pos = $j['posicion'] ?? '';
    $map = [
        'Portero' => 'GK',
        'Lateral izquierdo' => 'LB',
        'Lateral derecho' => 'RB',
        'Central izquierdo' => 'CB',
        'Central derecho' => 'CB',
        'Pivote' => 'CDM',
        'Interior izquierdo' => 'CM',
        'Interior derecho' => 'CM',
        'Extremo izquierdo' => 'LW',
        'Extremo derecho' => 'RW',
        'Delantero centro (9)' => 'ST',
    ];
    if ($sub !== '' && isset($map[$sub])) {
        return $map[$sub];
    }
    if ($pos === 'Portero') {
        return 'GK';
    }
    if ($pos === 'Defensa') {
        return 'DEF';
    }
    if ($pos === 'Mediocampista') {
        return 'CM';
    }
    if ($pos === 'Delantero') {
        return 'FW';
    }
    return '—';
}

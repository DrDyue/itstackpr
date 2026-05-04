<?php

namespace App\Support;

use Illuminate\Database\QueryException;

class UserFacingDatabaseError
{
    public static function message(QueryException $exception): string
    {
        $text = self::exceptionText($exception);

        // Tehnisko SQL kļūdu pārvēršam lietotājam saprotamā tekstā,
        // lai interfeiss nerādītu draivera iekšējo ziņojumu vai stack informāciju.
        if (str_contains($text, 'duplicate entry') || str_contains($text, 'integrity constraint violation: 1062')) {
            return 'Ierakstu nevar saglabāt, jo šāda vērtība jau eksistē. Pārbaudi e-pastu, kodu vai citu unikālu lauku un mēģini vēlreiz.';
        }

        if (str_contains($text, 'cannot delete or update a parent row') || str_contains($text, 'integrity constraint violation: 1451')) {
            return 'Ierakstu nevar dzēst, jo tam vēl ir piesaistīti citi dati. Vispirms atsaisti vai pārvieto saistītos ierakstus.';
        }

        if (str_contains($text, 'cannot add or update a child row') || str_contains($text, 'integrity constraint violation: 1452')) {
            return 'Ierakstu nevar saglabāt, jo izvēlētā saistītā vērtība vairs nav pieejama. Atjauno lapu un mēģini vēlreiz.';
        }

        if (str_contains($text, 'doesn\'t have a default value') || str_contains($text, 'unknown column') || str_contains($text, 'base table or view not found')) {
            return 'Datubāzes struktūra nav pilnībā sinhronizēta ar aplikāciju. Palaid migrācijas vai sazinies ar administratoru.';
        }

        if (str_contains($text, 'data too long') || str_contains($text, 'numeric value out of range')) {
            return 'Ievadītie dati ir pārāk gari vai neatbilst atļautajam formātam. Saīsini vērtības un mēģini vēlreiz.';
        }

        return 'Darbību šobrīd nevar pabeigt datubāzes kļūdas dēļ. Mēģini vēlreiz pēc brīža.';
    }

    public static function title(QueryException $exception): string
    {
        $text = self::exceptionText($exception);

        if (str_contains($text, 'doesn\'t have a default value') || str_contains($text, 'unknown column') || str_contains($text, 'base table or view not found')) {
            return 'Datubāze nav sinhronizēta';
        }

        return 'Darbību nevar pabeigt';
    }

    public static function status(QueryException $exception): int
    {
        $text = self::exceptionText($exception);

        // Nesinhronizētas shēmas kļūdas marķējam kā 503, jo problēma ir servera/vides pusē.
        // Datu validācijas un saistību kļūdām paliek 422 kā lietotāja darbības kļūdai.
        if (str_contains($text, 'doesn\'t have a default value') || str_contains($text, 'unknown column') || str_contains($text, 'base table or view not found')) {
            return 503;
        }

        return 422;
    }

    private static function exceptionText(QueryException $exception): string
    {
        return strtolower($exception->getMessage());
    }
}

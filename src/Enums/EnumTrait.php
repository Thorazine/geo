<?php

namespace Thorazine\Geo\Enums;

trait EnumTrait
{
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $key => $value) {
            $options[__(self::translationPath().$value->value)] = $value->value;
        }
        return $options;
    }

    public static function all(): array
    {
        return array_flip(self::options());
    }

    public static function rules(): string
    {
        return 'in:'.implode(',', self::all());
    }
}

<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->disabled()
                    ->dehydrated()
                    ->unique(ignoreRecord: true),

                TextInput::make('password')
                    ->label('Ubah Password')
                    ->password()
                    ->revealable()
                    ->required(fn(string $operation): bool => $operation === 'create')
                    ->dehydrated(fn($state): bool => filled($state))
                    ->dehydrateStateUsing(fn($state): string => bcrypt($state))
                    ->maxLength(255),

                Toggle::make('is_admin')
                    ->label('Admin')
                    ->default(false),
            ]);
    }
}
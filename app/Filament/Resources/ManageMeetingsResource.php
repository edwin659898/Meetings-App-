<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ManageMeetingsResource\Pages;
use App\Filament\Resources\ManageMeetingsResource\RelationManagers;
use App\Models\ManageMeetings;
use App\Models\Meeting;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Layout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Card;

class ManageMeetingsResource extends Resource
{
    protected static ?string $model = Meeting::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';


    public static function form(Form $form): Form
    {
        return $form

            ->schema([
                Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')->required()->label('Name of Meeting'),
                        Forms\Components\TextInput::make('location')->required(),
                    ])->columns(2),
                Card::make()
                    ->schema([
                        Forms\Components\Select::make('frequency')
                            ->options([
                                'Monthly' => 'Monthly',
                                'Weekly' => 'Weekly',
                                'Quarterly' => 'Quarterly',
                                'Yearly' => 'Yearly',
                                'One-time meeting' => 'One-time meeting',
                                'Fortnight' => 'Fortnight',
                            ])->required(),
                        Forms\Components\DateTimePicker::make('start_time')->required(),
                        Forms\Components\DateTimePicker::make('end_time')->required(),

                    ])->columns(3),
                Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('zoom_link')->rules(['url']),
                        Forms\Components\TextInput::make('meeting_id'),
                        Forms\Components\TextInput::make('passcode'),
                    ])->columns(3),
                Card::make()
                    ->schema([
                        Forms\Components\MultiSelect::make('meeting_user')
                            ->label('Members')
                            ->relationship('user', 'name')
                            ->searchable(),
                    ]),


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->Searchable(),
                Tables\Columns\TextColumn::make('name')->sortable()->Searchable(),
                Tables\Columns\TextColumn::make('location')->sortable()->Searchable(),
                Tables\Columns\TextColumn::make('frequency')->sortable()->Searchable(),
                Tables\Columns\TextColumn::make('start_time')->Searchable(),
            ])
            ->filters([
                SelectFilter::make('location')
                    ->options([
                        'Head Office' => 'Head Office',
                        'Site' => 'Site',
                    ]),
                SelectFilter::make('frequency')
                    ->options([
                        'Monthly' => 'Monthly',
                        'Weekly' => 'Weekly',
                        'Quarterly' => 'Quarterly',
                        'Yearly' => 'Yearly',
                        'One-time meeting' => 'One-time meeting',
                    ])
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->color('success'),
                Tables\Actions\EditAction::make()->visible(fn (Meeting $record): bool => auth()->user()->isThisMeetingChairman($record->id) == true
                    || auth()->user()->hasRole('DDC')),
                Tables\Actions\DeleteAction::make()->visible(auth()->user()->hasRole('DDC')),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UserRelationManager::class,
        ];
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListManageMeetings::route('/'),
            'create' => Pages\CreateManageMeetings::route('/create'),
            'edit' => Pages\EditManageMeetings::route('/{record}/edit'),
            'view' => Pages\ViewMeeting::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        if (auth()->user()->hasRole('DDC')) {
            return parent::getEloquentQuery()->with('user')->orderBy('created_at', 'Desc');
        } else {
            return parent::getEloquentQuery()
                ->WhereHas('members', function ($query) {
                    $query->where('user_id', auth()->id());
                })->orderBy('created_at', 'Desc');
        }
    }
}

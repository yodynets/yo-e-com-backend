<?php

declare(strict_types = 1);

namespace Yeod\Modules\Catalog\Presentation\Filament\Resources\CategoryResource\Tables;

use App\Models\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('parent.name')->default('-')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->searchable()->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('children_count')->counts('children')->badge()->color('info'),
                TextColumn::make('code')->numeric()->sortable(),
                TextColumn::make('ref')->searchable(),
                IconColumn::make('is_active')->boolean(),
                IconColumn::make('is_top')->boolean(),
                TextColumn::make('menu_columns_count')->numeric()->sortable(),
                TextColumn::make('sort_order')->numeric()->sortable(),
                TextColumn::make('created_at')
                          ->dateTime('d M Y')
                          ->sortable()
                          ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                          ->dateTime('d M Y')
                          ->sortable()
                          ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                            ->label('Status')
                            ->options([
                                '1' => 'Active',
                                '0' => 'Inactive',
                            ]),
                SelectFilter::make('parent_id')
                            ->label('Parent category')
                            ->options(
                                Category::whereNull('parent_id')
                                        ->pluck('name', 'id'),
                            )
                            ->placeholder('All categories'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

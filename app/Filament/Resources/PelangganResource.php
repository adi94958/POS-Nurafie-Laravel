<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PelangganResource\Pages;
use App\Models\Pelanggan;
use Filament\Facades\Filament;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class PelangganResource extends Resource
{
    protected static ?string $model = Pelanggan::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $label = 'Pelanggan';
    protected static ?string $recordTitleAttribute = 'nama_pelanggan';
    protected static ?string $pluralLabel = 'Pelanggan';
    protected static ?string $navigationLabel = 'Pelanggan';
    protected static ?string $navigationGroup = 'Data Master';
    protected static ?int $navigationSort = 0;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\Section::make('Form Pemasok')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextInput::make('nama_pelanggan')
                                    ->label('Nama Pelanggan')
                                    ->required()
                                    ->maxLength(255),
                                Components\TextInput::make('no_telp')
                                    ->label('Nomor Telepon')
                                    ->required()
                                    ->numeric()
                                    ->minLength(10)
                                    ->maxLength(15),
                                Components\TextInput::make('alamat')
                                    ->label('Alamat')
                                    ->required()
                                    ->maxLength(255),
                            ])
                    ])
                    ->collapsible(),
                Components\Hidden::make('id_pemilik')
                    ->default(fn() => Filament::auth()->id())
                    ->dehydrated(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn() => Pelanggan::query()->where('id_pemilik', Filament::auth()->user()->id))
            ->columns([
                TextColumn::make('nama_pelanggan')
                    ->label('Nama Pelanggan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('no_telp')
                    ->label('Nomor Telepon')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('alamat')
                    ->label('Alamat')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->successNotificationTitle('Data berhasil dihapus')
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Hapus yang Dipilih')
                    ->action(function ($records) {
                        $records->each->delete();
                        Notification::make()
                            ->title('Data berhasil dihapus')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Data Pelanggan')
                    ->schema([
                        Split::make([
                            Grid::make(2)
                                ->schema([
                                    Group::make([
                                        TextEntry::make('nama_pelanggan')
                                            ->label('Nama Pelanggan'),
                                        TextEntry::make('no_telp')
                                            ->label('Nomor Telepon'),
                                    ]),
                                    Group::make([
                                        TextEntry::make('alamat')
                                            ->label('Alamat'),
                                        TextEntry::make('created_at')
                                            ->label('Dibuat pada')
                                            ->formatStateUsing(fn($state) => \Carbon\Carbon::parse($state)->translatedFormat('d M Y, \\J\\a\\m H:i')),
                                    ]),
                                ]),
                        ])->from('lg'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewPelanggan::class,
            Pages\RiwayatTransaksi::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPelanggans::route('/'),
            'create' => Pages\CreatePelanggan::route('/create'),
            'edit' => Pages\EditPelanggan::route('/{record}/edit'),
            'view' => Pages\ViewPelanggan::route('/{record}'),
            'riwayat-transaksi' => Pages\RiwayatTransaksi::route('/{record}/riwayat-transaksi'),
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('view', ['record' => $record]);
    }
}

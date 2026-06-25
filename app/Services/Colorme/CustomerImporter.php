<?php

namespace App\Services\Colorme;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerImporter
{
    private const array REQUIRED_COLUMNS = ['顧客ID', '名前'];

    public function __construct(
        private readonly CsvReader $csvReader,
        private readonly ImportRowValidator $rowValidator,
    ) {}

    /**
     * @return array{imported: int, skipped: int, errors: int, log_path: string}
     */
    public function import(string $customerCsvPath): array
    {
        $logger = new ImportLogger('customers');

        foreach ($this->csvReader->rows($customerCsvPath) as $payload) {
            $line = $payload['line'];
            $row = $payload['row'];

            if (! $this->rowValidator->validateOrSkip($line, $row, self::REQUIRED_COLUMNS, $logger)) {
                continue;
            }

            try {
                DB::transaction(function () use ($row, $logger): void {
                    $colormeId = (int) preg_replace('/[^\d]/', '', $row['顧客ID']);
                    $userId = $this->resolveUserId($row);

                    Customer::query()->updateOrCreate(
                        ['colorme_customer_id' => $colormeId],
                        [
                            'user_id' => $userId,
                            'name' => $row['名前'],
                            'name_kana' => $this->nullable($row['フリガナ'] ?? null),
                            'email' => $this->normalizeEmail($row['メールアドレス'] ?? null),
                            'postal_code' => $this->normalizePostalCode($row['郵便番号'] ?? null),
                            'prefecture' => $this->nullable($row['都道府県名'] ?? null),
                            'address_line1' => $this->nullable($row['住所'] ?? null),
                            'address_line2' => null,
                            'phone' => $this->nullable($row['電話番号'] ?? null),
                            'mobile' => $this->nullable($row['携帯番号'] ?? null),
                            'note' => $this->nullable($row['備考'] ?? null),
                            'registered_at' => null,
                        ],
                    );

                    $logger->imported();
                });
            } catch (\Throwable $e) {
                $logger->error($line, $e->getMessage(), ['row' => $row]);
            }
        }

        return $logger->finish();
    }

    /**
     * @param  array<string, string>  $row
     */
    private function resolveUserId(array $row): ?int
    {
        if (! $this->isRegisteredMember($row['ユーザー登録'] ?? '')) {
            return null;
        }

        $email = $this->normalizeEmail($row['メールアドレス'] ?? null);

        if ($email === null) {
            return null;
        }

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $row['名前'],
                'password' => Str::password(32),
                'is_admin' => false,
                'email_verified_at' => now(),
            ],
        );

        return $user->id;
    }

    private function isRegisteredMember(string $value): bool
    {
        return trim($value) === '有';
    }

    private function normalizeEmail(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));

        return $email === '' ? null : $email;
    }

    private function normalizePostalCode(?string $postalCode): ?string
    {
        $digits = preg_replace('/[^\d]/', '', (string) $postalCode);

        if ($digits === '') {
            return null;
        }

        return str_pad(substr($digits, 0, 7), 7, '0', STR_PAD_LEFT);
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

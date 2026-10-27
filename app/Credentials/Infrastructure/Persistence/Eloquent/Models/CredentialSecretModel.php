<?php

namespace App\Credentials\Infrastructure\Persistence\Eloquent\Models;

use App\Identity\Infrastructure\Persistence\Eloquent\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $credential_reference_id
 * @property string $ciphertext
 * @property string $key_id
 * @property int $version
 * @property int|null $rotated_by
 * @property-read CredentialModel $credential
 * @property-read User|null $rotatedBy
 */
class CredentialSecretModel extends Model
{
    protected $table = 'credential_secrets';

    protected $fillable = [
        'credential_reference_id',
        'ciphertext',
        'key_id',
        'version',
        'rotated_by',
    ];

    public function __toString(): string
    {
        return '[secreto cifrado]';
    }

    /**
     * @return BelongsTo<CredentialModel, $this>
     */
    public function credential(): BelongsTo
    {
        return $this->belongsTo(CredentialModel::class, 'credential_reference_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function rotatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rotated_by');
    }
}

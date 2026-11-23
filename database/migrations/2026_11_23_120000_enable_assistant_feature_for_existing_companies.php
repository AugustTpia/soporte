<?php

use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Activa el asistente IA para las empresas que ya existían.
     *
     * El interruptor vive en companies.settings -> features -> assistant
     * (convención existente de CompanyFeature, sin columna nueva).
     * Las empresas sin ese ajuste se consideran con el asistente
     * activado (Company::assistantEnabled() devuelve true por defecto),
     * así que esta migración solo deja el valor explícito para que el
     * formulario lo muestre ya marcado y quede constancia del default.
     */
    public function up(): void
    {
        CompanyModel::query()->cursor()->each(function (CompanyModel $company): void {
            $settings = is_array($company->settings) ? $company->settings : [];
            $features = is_array($settings['features'] ?? null) ? $settings['features'] : [];

            if (array_key_exists('assistant', $features)) {
                return;
            }

            $features['assistant'] = true;
            $settings['features'] = $features;
            $company->settings = $settings;
            $company->save();
        });
    }

    public function down(): void
    {
        CompanyModel::query()->cursor()->each(function (CompanyModel $company): void {
            $settings = is_array($company->settings) ? $company->settings : [];
            $features = is_array($settings['features'] ?? null) ? $settings['features'] : [];

            if (! array_key_exists('assistant', $features)) {
                return;
            }

            unset($features['assistant']);
            $settings['features'] = $features;
            $company->settings = $settings;
            $company->save();
        });
    }
};

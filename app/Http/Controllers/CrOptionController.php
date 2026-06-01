<?php

namespace App\Http\Controllers;

use App\Models\CrOption;
use Illuminate\Http\Request;

class CrOptionController extends Controller
{
    public const TYPES = [
        'change_type' => 'Tipe Change',
        'category'    => 'Kategori',
        'priority'    => 'Prioritas',
    ];

    public function index(string $type)
    {
        $this->authorize('manage cr_options');

        abort_unless(array_key_exists($type, self::TYPES), 404);

        $typeLabel = self::TYPES[$type];
        $options   = CrOption::where('type', $type)->orderBy('label')->get();

        return view('cr_options.index', [
            'title'       => 'CR Options — ' . $typeLabel,
            'breadcrumbs' => [
                'Dashboard'  => route('dashboard'),
                'CR Options' => route('cr-options.index', 'change_type'),
                $typeLabel   => '#',
            ],
            'type'      => $type,
            'typeLabel' => $typeLabel,
            'options'   => $options,
            'types'     => self::TYPES,
        ]);
    }

    public function store(Request $request, string $type)
    {
        $this->authorize('manage cr_options');

        abort_unless(array_key_exists($type, self::TYPES), 404);

        $validated = $request->validate([
            'value'       => 'required|string|max:100|alpha_dash',
            'label'       => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $exists = CrOption::where('type', $type)->where('value', $validated['value'])->exists();
        if ($exists) {
            return back()->withErrors(['value' => 'Value "' . $validated['value'] . '" sudah ada untuk tipe ini.'])->withInput();
        }

        CrOption::create([
            'type'        => $type,
            'value'       => $validated['value'],
            'label'       => $validated['label'],
            'description' => $validated['description'] ?? null,
            'is_active'   => true,
        ]);

        return back()->with('success', 'Option berhasil ditambahkan.');
    }

    public function update(Request $request, string $type, CrOption $crOption)
    {
        $this->authorize('manage cr_options');

        abort_unless(array_key_exists($type, self::TYPES), 404);

        $validated = $request->validate([
            'label'       => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'is_active'   => 'boolean',
        ]);

        $crOption->update([
            'label'       => $validated['label'],
            'description' => $validated['description'] ?? null,
            'is_active'   => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Option berhasil diperbarui.');
    }

    public function toggleActive(string $type, CrOption $crOption)
    {
        $this->authorize('manage cr_options');

        abort_unless(array_key_exists($type, self::TYPES), 404);

        $crOption->update(['is_active' => !$crOption->is_active]);

        $status = $crOption->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Option berhasil {$status}.");
    }
}

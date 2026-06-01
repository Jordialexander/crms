<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRule;
use Illuminate\Http\Request;

class ApprovalRuleController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('manage approval_rules');
        $query = ApprovalRule::query();
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where('name', 'like', "%{$s}%");
        }
        $rules = $query->orderByDesc('enabled')->orderBy('priority')->orderBy('id')->paginate(15)->withQueryString();
        return view('approval_rule.index', [
            'title' => 'Approval Rules',
            'breadcrumbs' => ['Dashboard' => route('dashboard'), 'Approval Rules' => '#'],
            'rules' => $rules,
        ]);
    }

    public function create()
    {
        $this->authorize('manage approval_rules');
        return view('approval_rule.create', [
            'title' => 'Tambah Approval Rule',
            'breadcrumbs' => ['Dashboard' => route('dashboard'), 'Approval Rules' => route('approval-rule.index'), 'Tambah' => '#'],
            'rule' => null,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage approval_rules');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'change_type' => 'nullable|in:standard,normal,emergency',
            'category' => 'nullable|in:infrastructure,application,database,network,security,other',
            'priority' => 'required|in:low,medium,high,critical',
            'max_levels' => 'required|integer|min:1|max:10',
            'enabled' => 'nullable|boolean',
        ]);

        ApprovalRule::create([
            ...$validated,
            'enabled' => $request->boolean('enabled', true),
            'category' => $validated['category'] ?: null,
            'change_type' => $validated['change_type'] ?: null,
        ]);

        return redirect()->route('approval-rule.index')->with('success', 'Approval rule berhasil ditambahkan.');
    }

    public function edit(ApprovalRule $approvalRule)
    {
        $this->authorize('manage approval_rules');
        return view('approval_rule.edit', [
            'title' => 'Edit Approval Rule',
            'breadcrumbs' => ['Dashboard' => route('dashboard'), 'Approval Rules' => route('approval-rule.index'), $approvalRule->name => '#'],
            'rule' => $approvalRule,
        ]);
    }

    public function update(Request $request, ApprovalRule $approvalRule)
    {
        $this->authorize('manage approval_rules');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'change_type' => 'nullable|in:standard,normal,emergency',
            'category' => 'nullable|in:infrastructure,application,database,network,security,other',
            'priority' => 'required|in:low,medium,high,critical',
            'max_levels' => 'required|integer|min:1|max:10',
            'enabled' => 'nullable|boolean',
        ]);

        $approvalRule->update([
            ...$validated,
            'enabled' => $request->boolean('enabled', true),
            'category' => $validated['category'] ?: null,
            'change_type' => $validated['change_type'] ?: null,
        ]);

        return redirect()->route('approval-rule.index')->with('success', 'Approval rule berhasil diperbarui.');
    }

    public function destroy(ApprovalRule $approvalRule)
    {
        $this->authorize('manage approval_rules');
        $approvalRule->delete();
        return redirect()->route('approval-rule.index')->with('success', 'Approval rule berhasil dihapus.');
    }
}


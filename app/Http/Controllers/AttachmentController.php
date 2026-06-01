<?php

namespace App\Http\Controllers;

use App\Models\ChangeRequest;
use App\Models\CrAttachment;
use App\Models\ImplementationLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function download(ChangeRequest $cr, CrAttachment $attachment)
    {
        if ($attachment->change_request_id !== $cr->id) {
            abort(404);
        }

        $this->authorizeViewCr($cr);

        if (!Storage::disk('public')->exists($attachment->filename)) {
            abort(404, 'File tidak ditemukan.');
        }

        return Storage::disk('public')->download($attachment->filename, $attachment->original_name);
    }

    public function downloadEvidence(ChangeRequest $cr, ImplementationLog $log)
    {
        if ($log->change_request_id !== $cr->id) {
            abort(404);
        }

        $this->authorizeViewCr($cr);

        if (!$log->evidence_file || !Storage::disk('public')->exists($log->evidence_file)) {
            abort(404, 'File evidence tidak ditemukan.');
        }

        $ext      = pathinfo($log->evidence_file, PATHINFO_EXTENSION);
        $filename = 'evidence_' . $cr->cr_number . '_' . $log->id . '.' . $ext;

        return Storage::disk('public')->download($log->evidence_file, $filename);
    }

    private function authorizeViewCr(ChangeRequest $cr): void
    {
        $user = Auth::user();

        if ($user->can('view all change_request')) {
            return;
        }

        if ($user->can('view team change_request')) {
            return;
        }

        // Requester, PIC, approver dalam chain, atau approver terakhir
        $chain = $cr->approver_chain ?? [];
        if (
            $cr->requester_id === $user->id ||
            $cr->pic_id       === $user->id ||
            $cr->approver_id  === $user->id ||
            in_array($user->id, $chain)
        ) {
            return;
        }

        abort(403);
    }
}

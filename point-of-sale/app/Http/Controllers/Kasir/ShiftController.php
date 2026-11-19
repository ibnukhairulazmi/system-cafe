<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kasir\CloseShiftRequest;
use App\Http\Requests\Kasir\OpenShiftRequest;
use App\Models\Shift;
use App\Services\ShiftService;
use App\Support\Format;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function __construct(
        private readonly ShiftService $shiftService
    ) {}

    public function index(Request $request): View
    {
        $user  = $request->user();
        $shift = $this->shiftService->getActiveShift($user);

        return view('kasir.shift', [
            'shift'   => $shift,
            'summary' => $shift ? $this->shiftService->summary($shift) : null,
            'recent'  => $this->shiftService->getRecentClosedShifts($user, 5),
        ]);
    }

    public function open(OpenShiftRequest $request): RedirectResponse
    {
        try {
            $this->shiftService->open($request->user(), (float) $request->validated('opening_cash'));
        } catch (DomainException $e) {
            return redirect()->route('kasir.shift.index')->with('error', $e->getMessage());
        }

        return redirect()->route('kasir.pos')->with('success', 'Shift dibuka. Selamat bekerja!');
    }

    public function close(CloseShiftRequest $request): RedirectResponse
    {
        $shift = $this->shiftService->getActiveShift($request->user());

        if (! $shift) {
            return redirect()->route('kasir.shift.index')->with('error', 'Tidak ada shift yang berjalan.');
        }

        $this->authorize403($request, $shift);

        try {
            $closed = $this->shiftService->close(
                $shift,
                $request->user(),
                (float) $request->validated('closing_cash'),
                $request->validated('note'),
            );
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $diff = (float) $closed->difference;
        $message = 'Shift ditutup. ' . ($diff == 0.0
            ? 'Kas tunai sesuai dengan sistem.'
            : 'Selisih kas: ' . ($diff > 0 ? 'lebih ' : 'kurang ') . Format::rupiah(abs($diff)) . '.');

        return redirect()->route('kasir.shift.index')->with('success', $message);
    }

    private function authorize403(Request $request, Shift $shift): void
    {
        abort_unless($request->user()->can('close', $shift), 403);
    }
}

<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-orange-50 border-b border-orange-200">
                <th class="text-left py-2 px-3 text-orange-700 font-semibold">Tanggal</th>
                <th class="text-left py-2 px-3 text-orange-700 font-semibold">Keterangan</th>
                <th class="text-right py-2 px-3 text-orange-700 font-semibold">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($operasionalDetails as $detail)
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-2 px-3 text-gray-600">
                        {{ \Carbon\Carbon::parse($detail->created_at)->format('d/m/Y H:i') }}
                    </td>
                    <td class="py-2 px-3 text-gray-800">
                        {{ $detail->description }}
                    </td>
                    <td class="py-2 px-3 text-right font-semibold text-orange-600">
                        Rp {{ number_format($detail->amount, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        @if(isset($showTotal) && $showTotal)
        <tfoot>
            <tr class="bg-orange-50 font-bold">
                <td colspan="2" class="py-2 px-3 text-orange-700">TOTAL OPERASIONAL</td>
                <td class="py-2 px-3 text-right text-orange-700">
                    Rp {{ number_format($operasional, 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
        @endif
    </table>
    <!-- Pagination Links -->
    @if($operasionalDetails->hasPages())
    <div class="mt-4 px-3 pb-2 flex justify-end">
        {{ $operasionalDetails->appends(request()->except('page'))->links() }} 
    </div>
    @endif
</div>

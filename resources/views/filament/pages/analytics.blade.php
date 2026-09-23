<x-filament-panels::page>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <x-filament::section>
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Revenue</p>
                <p class="text-3xl font-bold text-primary-600">
                    ${{ number_format($totalRevenue, 2) }}
                </p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Monthly Revenue</p>
                <p class="text-3xl font-bold text-success-600">
                    ${{ number_format($monthlyRevenue, 2) }}
                </p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Occupancy Rate</p>
                <p class="text-3xl font-bold text-info-600">
                    {{ $occupancyRate }}%
                </p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Avg Booking Value</p>
                <p class="text-3xl font-bold text-warning-600">
                    ${{ number_format($avgBookingValue, 2) }}
                </p>
            </div>
        </x-filament::section>
    </div>

    {{-- Top Hotels --}}
    <x-filament::section heading="🏆 Top Performing Hotels">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b dark:border-gray-700">
                    <th class="text-left py-2">No.</th>
                    <th class="text-left py-2">Hotel</th>
                    <th class="text-left py-2">City</th>
                    <th class="text-right py-2">Total Bookings</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($topHotels as $index => $hotel)
                    <tr class="border-b dark:border-gray-700">
                        <td class="py-3 font-bold text-primary-600">{{ $index + 1 }}</td>
                        <td class="py-3 font-semibold">{{ $hotel->name }}</td>
                        <td class="py-3 text-gray-500">{{ $hotel->city }}, {{ $hotel->country }}</td>
                        <td class="py-3 text-right">
                            <x-filament::badge color="info">
                                {{ $hotel->bookings_count }}
                            </x-filament::badge>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-filament::section>

</x-filament-panels::page>

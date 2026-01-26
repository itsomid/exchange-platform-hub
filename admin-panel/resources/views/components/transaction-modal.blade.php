@props(['modalId', 'title', 'user' => null, 'transactions', 'routeName', 'routeParam', 'routeParamValue'])

<div class="modal fade" id="{{ $modalId }}" tabindex="-1"  role="dialog" aria-hidden="true">>
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header justify-content-between">
                <h5 class="modal-title font-number" id="exampleModalLabel4">{{ $title }}</h5>

                <div class="d-flex flex-column ">
                    <a href="" class="text-heading text-truncate">
                        <span class="h6 fw-medium">{{ $user->email }}</span>
                        <span class="me-2">({{ $user->username }})</span>
                    </a>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive text-nowrap">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>شناسه</th>
                                <th>نوع تراکنش</th>
                                <th>رمز ارز</th>
                                <th>مقدار</th>
                                <th>مقدار موجودی</th>
                                <th>توضیحات</th>
                                <th>تاریخ و زمان</th>
                                <th>وضعیت</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @if ($transactions->isEmpty())
                                <tr>
                                    <td colspan="8" class="text-center">
                                        تراکنشی یافت نشد.
                                    </td>
                                </tr>
                            @else
                                @foreach ($transactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->id }}</td>
                                        <td class="text-heading fw-medium">
                                            <div class="d-flex justify-content-start align-items-center">
                                                <div
                                                    class="trans-avatar-group d-flex align-items-center assigned-avatar">
                                                    <div class="avatar avatar-md ">
                                                        <img src="{{ asset($transaction->wallet->currency->coinLogo()) }}"
                                                            class="rounded-circle">
                                                    </div>
                                                    <div class="avatar avatar-md">
                                                        <span
                                                            class="avatar-initial rounded-circle bg-label-{{ $transaction->type->color() }}">
                                                            <i
                                                                class="fa-regular fa-{{ $transaction->type->icon() }} mx-3"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="d-flex flex-column align-items-start">
                                                    <span class="badge bg-label-{{ $transaction->type->color() }} ms-2">
                                                        {{ $transaction->type->label() }}
                                                    </span>
                                                    @if ($transaction->subtype->value != 'user_initiated')
                                                        <span class="badge bg-label-secondary ms-2 mt-2">
                                                            {{ $transaction->subtype->label() }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $transaction->wallet->currency_symbol }}</td>
                                        <td class="font-number" dir="ltr">
                                            <h6
                                                class="mb-0 {{ $transaction->amount > 0 ? 'text-success' : 'text-danger' }}">
                                                {{ formatNumberTrimZeros($transaction->amount) }}
                                            </h6>
                                        </td>
                                        <td class="font-number">
                                            <h6 class="mb-0">
                                                {{ formatNumberTrimZeros($transaction->balance) }}
                                            </h6>
                                        </td>
                                        <td class="font-number text-wrap">
                                            <span>{{ $transaction->description }}</span>
                                            @if ($transaction->admin_id)
                                                <p class="text-muted">توسط ادمین
                                                    ({{ $transaction->admin->last_name }})
                                                </p>
                                            @endif
                                        </td>
                                        <td class="font-number">
                                            {{ \App\Helpers\DateFormatter::convertToPersianDate($transaction->created_at, 'H:i:s %Y/%m/%d') }}
                                        </td>
                                        <td>
                                            <span class="badge bg-label-{{ $transaction->status->color() }}">
                                                {{ $transaction->status->label() }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <a type="button" href="{{ route($routeName, [$routeParam => $routeParamValue]) }}"
                    class="btn btn-primary">لیست تراکنش ها</a>
                <button type="button" class="btn btn-label-secondary waves-effect" data-bs-dismiss="modal">بستن
                </button>
            </div>
        </div>
    </div>
</div>

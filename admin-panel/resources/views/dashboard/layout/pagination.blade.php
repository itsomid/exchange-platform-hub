<nav aria-label="Page navigation">
    <ul class="pagination pagination-sm">
        @if(!is_null($collection->prev_page_url))
            <li class="page-item">
                <a class="page-link" href="{{route(\Illuminate\Support\Facades\Route::current()->getName(), ['page' => $collection->current_page - 1])}}">{{$collection->current_page - 1}}</a>
            </li>
        @endif
        <li class="page-item active">
            <a class="page-link">{{$collection->current_page}}</a>
        </li>
        @if(!is_null($collection->next_page_url))
                <li class="page-item">
                    <a class="page-link" href="{{route(\Illuminate\Support\Facades\Route::current()->getName(), ['page' => $collection->current_page + 1])}}">{{$collection->current_page + 1}}</a>
                </li>
        @endif
    </ul>
</nav>

@php
    if (request()->routeIs('home')) {
        $blogs = @getContent('blog.element', limit: 3);
    } else {
        $blogs = App\Models\Frontend::where('data_keys', 'blog.element')->orderBy('id', 'DESC')->paginate(getPaginate());
    }
@endphp

<!-- ============================ Blog Section Start ========================= -->
<section class="blog pb-60 pt-120">
    <div class="container">
        <div class="row gy-sm-5 gy-4 justify-content-center">

            @forelse($blogs as $blog)
                <div class="col-sm-6">
                    <div class="blog-item">
                        <div class="blog-item__thumb">
                            <a href="{{ route('blog.details', [slug(@$blog->data_values->title)]) }}" class="blog-item__thumb-link">
                                <img src="{{ getImage('assets/images/frontend/blog/' .@$blog->data_values->image, '820x450') }}" alt="@lang('Blog')">
                            </a>
                        </div>
                        <div class="blog-item__content">
                            <span class="blog-item__date">{{ showDateTime($blog->created_at, 'M d, Y') }}</span>
                            <h3 class="blog-item__title">
                                <a href="{{ route('blog.details', [slug(@$blog->data_values->title)]) }}" class="blog-item__title-link">
                                    {{ strLimit(__($blog->data_values->title), 45) }}
                                </a>
                            </h3>
                            <a href="{{ route('blog.details', [slug(@$blog->data_values->title)]) }}" class="btn btn--simple base">@lang('Read More')</a>
                        </div>
                    </div>
                </div>
            @empty
                <x-empty-message h4="{{ true }}" />
            @endforelse

            @if (!request()->routeIs('home'))
                {{ paginateLinks($blogs) }}
            @endif

        </div>
    </div>
</section>
<!-- ============================ Blog Section End ========================= -->
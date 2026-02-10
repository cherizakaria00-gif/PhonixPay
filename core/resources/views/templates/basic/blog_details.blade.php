@extends($activeTemplate.'layouts.frontend')

@section('content')
<!-- ============================ Blog Details Section Start ========================= -->
<section class="blog-details pt-120 pb-60">
    <div class="container">
        <div class="row gy-5">
            <div class="col-xl-8 col-lg-7 pe-xl-5">
                <div class="row gy-4 blog-item-wrapper justify-content-center">
                    <div class="col-12">
                        <div class="blog-item">
                            <div class="blog-item__thumb">
                                <img src="{{ getImage('assets/images/frontend/blog/'.@$blog->data_values->image, '820x450') }}" alt="@lang('Blog')">
                            </div>
                            <div class="blog-item__content">
                                <ul class="blog-meta-list">
                                    <li class="blog-meta-list__item">
                                        <span class="blog-meta-list__icon"><i class="fas fa-calendar"></i></span>
                                        <span class="blog-meta-list__text">{{ showDateTime($blog->created_at, 'M d, Y') }}</span>
                                    </li>
                                </ul>
                                <div class="blog-item__info">
                                    <h3 class="blog-item__title">{{ __(@$blog->data_values->title) }}</h3>
                                    <p class="blog-item__desc">
										@php echo @$blog->data_values->description; @endphp
									</p>
                                </div>

                                <!-- Blog Comments Start -->
								<div class="fb-comments pt-5 comments" data-href="{{ route('blog.details',[slug(@$blog->data_values->title)]) }}" data-numposts="5">
								</div>
                                <!-- Blog Comments End -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-lg-5">
				<!-- ============================= Blog Details Sidebar Start ======================== -->
				<div class="blog-sidebar-wrapper">

					<div class="blog-sidebar">
						<h4 class="blog-sidebar__title">@lang('Latest Blogs')</h4>

						@foreach($latestBlogs as $data)
							<div class="latest-blog">
								<div class="latest-blog__thumb">
									<a href="{{ route('blog.details', [slug(@$data->data_values->title)]) }}">
										<img src="{{ getImage('assets/images/frontend/blog/thumb_'.@$data->data_values->image, '410x225') }}" alt="@lang('Blog')">
									</a>
								</div>
								<div class="latest-blog__content">
									<span class="latest-blog__title mt-0">
										<a href="{{ route('blog.details', [slug(@$data->data_values->title)]) }}">
											{{ strLimit(__($data->data_values->title), 55) }}
										</a>
									</span>
									<ul class="blog-meta-list font-small">
										<li class="blog-meta-list__item">
											<span class="blog-meta-list__icon"><i class="lar la-calendar"></i></span>
											<span class="blog-meta-list__text">{{ showDateTime($data->created_at, 'M d, Y') }}</span>
										</li>
									</ul>
								</div>
							</div>
						@endforeach

					</div>
				</div>
				<!-- ============================= Blog Details Sidebar End ======================== -->
            </div>
        </div>
    </div>
</section>
<!-- ============================ Blog Details Section End ========================= -->
@endsection

@push('fbComment')
	@php echo loadExtension('fb-comment') @endphp
@endpush

@php
    $product = @getContent('product.content', true)->data_values;
    $products = @getContent('product.element', orderById:true);
@endphp

<!-- ================================== Product & Payment Unlock Start ================================ -->
<section class="product-and-payment-unlock">
    <div class="product-and-payment-unlock__shapes">
        <div class="payment-unlock__left">
            <img src="{{ asset($activeTemplateTrue.'frontend/images/shapes/product-shape3.png') }}">
        </div>
        <div class="payment-unlock__bottom base"></div>
        <div class="payment-unlock__bottom base-two"></div>
    </div>
    <!-- ================================== Product Start ================================ -->
  <div class="product py-120">
      <div class="product-shape one"></div>
      <div class="product-shape two"></div>
      <div class="container">
          <div class="row justify-content-center">
              <div class="col-lg-12">
                  <div class="section-heading style-two">
                      <span class="section-heading__subtitle">{{ __(@$product->heading) }}</span>
                      <h2 class="section-heading__title">{{ __(@$product->subheading) }}</h2>
                  </div>
              </div>
          </div>
          <div class="row gy-4 justify-content-center">
            @foreach($products as $data)
                <div class="col-md-4 col-sm-6 col-xsm-6">
                    <div class="product-item">
                        <h5 class="product-item__title">{{ __(@$data->data_values->title) }}</h5>
                        <p class="product-item__desc">{{ __(@$data->data_values->description) }}</p>
                    </div>
                </div>
            @endforeach
          </div>
      </div>
  </div>
  <!-- ================================== Product End ================================ -->
  
  <!-- ================================== Payment Unlock Start ================================ -->
  <div class="payment-unlock">
      <div class="container">
          <div class="row gy-5 align-items-center flex-wrap-reverse">
              <div class="col-md-6 d-md-block d-none">
                  <div class="payment-unlock-thumb">
                      <img src="{{ getImage('assets/images/frontend/product/' .@$product->image, '630x865') }}" alt="@lang('Product')">
                  </div>
              </div>
              <div class="col-md-6">
                  <div class="payment-unlock-content">
                      <div class="section-heading style-two">
                          <span class="section-heading__subtitle">{{ __(@$product->second_heading) }}</span>
                          <h2 class="section-heading__title">{{ __(@$product->second_subheading) }}</h2>
                          <p class="section-heading__desc">{{ __(@$product->description) }}</p>
                      </div>
                      <div class="payment-unlock-content__button">
                          <a href="{{ @$product->btn_url }}" class="btn btn--base">{{ __(@$product->btn_text) }}</a>
                      </div>
                  </div>
              </div>
          </div>
      </div>
  </div>
  
  <!-- ================================== Payment Unlock End ================================ -->
</section>
<!-- ================================== Product & Payment Unlock End ================================ -->
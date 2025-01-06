<div class="ps-faqs pt-40 pb-40">
    <div class="container">
        <div class="ps-section__header">
            <h1>{!! BaseHelper::clean($title) !!}</h1>
        </div>
        <div class="ps-section__content">
                <div class="ps-table--faqs">
                @foreach($categories as $category)
                    <div class="faq-group">
                        <div class="row">
                            <div class="col-md-3 col-sm-3 col-xs-12">
                                <h4 class="heading">{{ $category->name }}</h4>
                            </div>
                            <div class="col-md-9 col-sm-9 col-xs-12">
                                @foreach($category->faqs as $faq)
                                    <div class="faq-item">
                                        <div class="row">
                                            <div class="col-md-5 col-sm-5 col-xs-12">
                                                <p class="question">{{ $faq->question }}</p>
                                            </div>
                                            <div class="col-md-7 col-sm-7 col-xs-12">
                                                <p class="answer">{!! BaseHelper::clean($faq->answer) !!}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="element tabs-block">
  <div class="tab-element bl-tabs">
    <div class="bl-tabs-menu">
      <!-- Nav tabs -->
      <ul class="nav nav-tabs" role="tablist">
        <li role="presentation" class="active">
          <a href="#approved_user_panel" aria-controls="approved_user_panel" role="tab" class="tab-switch">일반회원</a>
        </li>
        <li role="presentation" class="">
          <a href="#partner_panel" aria-controls="partner_panel" role="tab" class="tab-switch">파트너</a>
        </li>
        <li role="presentation" class="">
          <a href="#vip_panel" aria-controls="vip_panel" role="tab" class="tab-switch">VIP</a>
        </li>
      </ul>
    </div>
    <!-- Tab panes -->
    <div class="tab-content">
      <div role="tabpanel" class="tab-pane fade active in form-group" id="approved_user_panel"> <?php
        get_template_part( 'woocommerce/myaccount/levelup-request-form', 'approved-user' ); ?>
      </div>
      <div role="tabpanel" class="tab-pane fade form-group" id="partner_panel"> <?php
        get_template_part( 'woocommerce/myaccount/levelup-request-form', 'partner' ); ?>
      </div>
      <div role="tabpanel" class="tab-pane fade form-group" id="vip_panel"> <?php
        get_template_part( 'woocommerce/myaccount/levelup-request-form', 'vip' ); ?>
      </div>
    </div>
  </div>
</div>

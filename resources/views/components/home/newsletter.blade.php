<section class="cream newsletter-wrap">
  <div class="container">
    <div class="newsletter">
      <h2>Join Our Kitchen Family</h2>
      <p>
        Subscribe for recipes, offers, and a welcome discount on your
        first order.
      </p>
      <form class="subscribe" id="newsletter-form" novalidate>
        <input
          type="email"
          name="email"
          aria-label="Email address"
          placeholder="Enter your email"
          required
        /><button type="submit">Subscribe</button>
      </form>
      <p id="newsletter-message" class="newsletter-message" role="status" aria-live="polite"></p>
    </div>
  </div>
</section>

<script>
(function () {
  var form = document.getElementById('newsletter-form');
  if (!form) return;
  var msg = document.getElementById('newsletter-message');

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var email = form.email.value.trim();
    var btn = form.querySelector('button');
    btn.disabled = true;
    msg.classList.remove('is-error');
    msg.textContent = '';

    fetch('{{ route('shop.newsletter.subscribe') }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify({ email: email }),
    })
      .then(function (res) {
        return res.json().then(function (data) { return { ok: res.ok, data: data }; });
      })
      .then(function (result) {
        if (result.ok) {
          form.reset();
          msg.textContent = result.data.message || 'Subscribed! Check your inbox for your welcome discount.';
        } else {
          msg.classList.add('is-error');
          msg.textContent = (result.data.errors && result.data.errors.email && result.data.errors.email[0])
            || result.data.message
            || 'Something went wrong. Please try again.';
        }
      })
      .catch(function () {
        msg.classList.add('is-error');
        msg.textContent = 'Something went wrong. Please try again.';
      })
      .finally(function () {
        btn.disabled = false;
      });
  });
})();
</script>

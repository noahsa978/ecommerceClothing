<style>
  /* Footer */
  footer {
    border-top: 1px solid var(--border);
    margin-top: 36px;
  }
  .footer-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 16px;
    padding: 24px 0;
    color: #cbd5e1;
  }
  .footer-row h4 {
    margin: 0 0 10px;
  }
  .footer-row a {
    color: #9ca3af;
  }
  .footer-row a:hover {
    color: #fff;
  }
  .social-icons {
    display: flex;
    gap: 10px;
    margin-top: 8px;
  }
  .copyright {
    color: #748094;
    padding: 12px 0;
    border-top: 1px solid var(--border);
  }

  @media (max-width: 900px) {
    .footer-row {
      grid-template-columns: 1fr;
    }
  }
</style>

<footer>
  <div class="container footer-row">
    <div>
      <h4>Explore</h4>
      <a href="./homepage.php">Home</a><br />
      <a href="./shop.php">Shop</a><br />
      <a href="#">About</a>
    </div>
    <div>
      <h4>Contact</h4>
      <p style="margin: 0; color: #9ca3af">
        123 Fashion Ave, Suite 100, NY 10001
      </p>
      <p style="margin: 6px 0; color: #9ca3af">Tel: (212) 555‑0199</p>
      <p style="margin: 0; color: #9ca3af">
        Email: support@ecomclothing.test
      </p>
    </div>
    <div>
      <h4>Help & Social</h4>
      <a href="#">Shipping & Returns</a><br />
      <a href="#">FAQ</a>
      <div class="social-icons">
        <a href="#" aria-label="Facebook" title="Facebook">
          <img
            src="../images/facebook.svg"
            width="22"
            height="22"
            alt="Facebook"
            style="filter: brightness(0.7)"
          />
        </a>
        <a href="#" aria-label="Instagram" title="Instagram">
          <img
            src="../images/instagram.svg"
            width="22"
            height="22"
            alt="Instagram"
            style="filter: brightness(0.7)"
          />
        </a>
        <a href="#" aria-label="Tiktok" title="Tiktok">
          <img
            src="../images/tiktok.svg"
            width="22"
            height="22"
            alt="Tiktok"
            style="filter: brightness(0.7)"
          />
        </a>
      </div>
    </div>
  </div>
  <div class="container copyright">
    <small
      >&copy; <span id="y"></span> EcomClothing. All rights reserved.</small
    >
  </div>
</footer>

<script>
  document.getElementById("y").textContent = new Date().getFullYear();
</script>
</body>
</html>
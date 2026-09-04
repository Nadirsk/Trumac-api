<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Welcome to Trumac</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:'Segoe UI',Helvetica,Arial,sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f1f5f9;padding:40px 16px;">
    <tr>
      <td align="center">
        <table width="560" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;width:100%;">

          {{-- ── HEADER ── --}}
          <tr>
            <td style="background:linear-gradient(135deg,#c1121f 0%,#e63946 60%,#ff6b6b 100%);border-radius:16px 16px 0 0;padding:36px 40px;text-align:center;">
              <div style="display:inline-block;background:rgba(255,255,255,0.15);border-radius:10px;padding:6px 18px;margin-bottom:14px;">
                <span style="font-size:11px;font-weight:700;color:rgba(255,255,255,0.9);letter-spacing:3px;text-transform:uppercase;">TRUMAC</span>
              </div>
              <div style="font-size:26px;font-weight:800;color:#ffffff;letter-spacing:1px;margin-bottom:6px;">
                Your Account is Ready
              </div>
              <div style="font-size:13px;color:rgba(255,255,255,0.75);letter-spacing:0.3px;">
                Distribution Management System
              </div>
            </td>
          </tr>

          {{-- ── BODY ── --}}
          <tr>
            <td style="background:#ffffff;padding:36px 40px 28px;">

              {{-- Greeting --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding-bottom:24px;">
                    <div style="font-size:22px;font-weight:700;color:#111827;margin-bottom:8px;">
                      Hi {{ $retailerName }}, welcome aboard! 🎉
                    </div>
                    <div style="font-size:14px;color:#6b7280;line-height:1.7;">
                      Your Trumac retailer account has been activated for
                      <strong style="color:#374151;">{{ $shopName }}</strong>.
                      Below are your login credentials to access the Trumac mobile app.
                    </div>
                  </td>
                </tr>
              </table>

              {{-- Section label --}}
              <div style="font-size:10px;font-weight:700;color:#9ca3af;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:12px;">
                Login Credentials
              </div>

              {{-- Credentials card --}}
              <table width="100%" cellpadding="0" cellspacing="0" style="border:1.5px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:20px;">

                {{-- Email row --}}
                <tr>
                  <td style="padding:16px 20px;border-bottom:1px solid #f3f4f6;">
                    <table cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="width:44px;vertical-align:middle;">
                          <div style="width:38px;height:38px;border-radius:10px;background:#eff6ff;text-align:center;line-height:38px;font-size:18px;">
                            ✉️
                          </div>
                        </td>
                        <td style="padding-left:14px;vertical-align:middle;">
                          <div style="font-size:10px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:1px;margin-bottom:3px;">Email (Username)</div>
                          <div style="font-size:15px;font-weight:600;color:#1d4ed8;">{{ $email }}</div>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                {{-- Password row --}}
                <tr>
                  <td style="padding:16px 20px;background:#fef9f9;">
                    <table cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="width:44px;vertical-align:middle;">
                          <div style="width:38px;height:38px;border-radius:10px;background:#fff1f2;text-align:center;line-height:38px;font-size:18px;">
                            🔑
                          </div>
                        </td>
                        <td style="padding-left:14px;vertical-align:middle;">
                          <div style="font-size:10px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">Password</div>
                          <div style="font-size:22px;font-weight:800;color:#c1121f;letter-spacing:4px;font-family:'Courier New',monospace;">{{ $password }}</div>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

              </table>

              {{-- Security notice --}}
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#fffbeb;border:1px solid #fde68a;border-left:4px solid #f59e0b;border-radius:10px;margin-bottom:28px;">
                <tr>
                  <td style="padding:14px 16px;">
                    <table cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="font-size:18px;vertical-align:top;padding-right:10px;padding-top:1px;">⚠️</td>
                        <td>
                          <div style="font-size:13px;font-weight:700;color:#92400e;margin-bottom:3px;">Keep your password private</div>
                          <div style="font-size:12px;color:#78350f;line-height:1.6;">Never share your password with anyone. We recommend changing it after your first login from Profile → Change Password.</div>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              {{-- Divider --}}
              <div style="height:1px;background:#f3f4f6;margin-bottom:24px;"></div>

              {{-- Steps label --}}
              <div style="font-size:10px;font-weight:700;color:#9ca3af;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:16px;">
                How to Login
              </div>

              {{-- Steps --}}
              <table width="100%" cellpadding="0" cellspacing="0">

                <tr>
                  <td style="padding-bottom:14px;">
                    <table cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="vertical-align:top;padding-top:1px;">
                          <div style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#e63946,#c1121f);text-align:center;line-height:26px;font-size:12px;font-weight:700;color:#fff;">1</div>
                        </td>
                        <td style="padding-left:12px;font-size:13px;color:#374151;line-height:1.6;vertical-align:middle;">
                          Download the <strong>Trumac</strong> mobile app from the Play Store or App Store
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <tr>
                  <td style="padding-bottom:14px;">
                    <table cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="vertical-align:top;padding-top:1px;">
                          <div style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#e63946,#c1121f);text-align:center;line-height:26px;font-size:12px;font-weight:700;color:#fff;">2</div>
                        </td>
                        <td style="padding-left:12px;font-size:13px;color:#374151;line-height:1.6;vertical-align:middle;">
                          Enter your <strong>email address</strong> as your username
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <tr>
                  <td style="padding-bottom:14px;">
                    <table cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="vertical-align:top;padding-top:1px;">
                          <div style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#e63946,#c1121f);text-align:center;line-height:26px;font-size:12px;font-weight:700;color:#fff;">3</div>
                        </td>
                        <td style="padding-left:12px;font-size:13px;color:#374151;line-height:1.6;vertical-align:middle;">
                          Enter the <strong>password</strong> above and tap Login
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <tr>
                  <td>
                    <table cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="vertical-align:top;padding-top:1px;">
                          <div style="width:26px;height:26px;border-radius:50%;background:#e5e7eb;text-align:center;line-height:26px;font-size:12px;font-weight:700;color:#6b7280;">4</div>
                        </td>
                        <td style="padding-left:12px;font-size:13px;color:#6b7280;line-height:1.6;vertical-align:middle;">
                          Change your password from Profile → Change Password after first login
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

              </table>

            </td>
          </tr>

          {{-- ── FOOTER ── --}}
          <tr>
            <td style="background:#1e293b;border-radius:0 0 16px 16px;padding:22px 40px;text-align:center;">
              <div style="font-size:13px;font-weight:600;color:#94a3b8;margin-bottom:4px;">Trumac Distribution Management</div>
              <div style="font-size:11px;color:#475569;line-height:1.6;">
                This is an automated email. If you did not expect this, please contact your area manager.
              </div>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>

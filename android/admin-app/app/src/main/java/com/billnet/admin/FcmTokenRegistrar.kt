package com.billnet.admin

import android.content.Context
import android.os.Build
import android.util.Log
import android.webkit.CookieManager
import com.google.android.gms.tasks.Tasks
import com.google.firebase.messaging.FirebaseMessaging
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import java.net.HttpURLConnection
import java.net.URL

object FcmTokenRegistrar {

    private const val TAG = "FCM"
    private const val PREFS = "fcm_registration"
    private const val KEY_REGISTERED = "registered"
    private const val KEY_REGISTERED_TOKEN = "registered_token"
    private const val KEY_REGISTERED_COOKIE_HASH = "registered_cookie_hash"
    private const val KEY_FCM_TOKEN = "fcm_token"

    /**
     * Kirim token FCM ke backend Laravel bila:
     * - sudah ada cookie session (user login di WebView), dan
     * - token belum pernah dikirim.
     */
    fun tryRegister(context: Context, endpoint: String, baseUrl: String) {
        val prefs = context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)
        val storedToken = prefs.getString(KEY_FCM_TOKEN, null)

        CoroutineScope(Dispatchers.IO).launch {
            val token = storedToken ?: runCatching {
                Tasks.await(FirebaseMessaging.getInstance().token)
            }.getOrNull()
            if (token.isNullOrBlank()) {
                Log.w(TAG, "Token FCM null/blank - Firebase tidak menghasilkan token")
                return@launch
            }
            Log.d(TAG, "Token FCM didapat: ${token.take(30)}...")

            val cookie = CookieManager.getInstance().getCookie(baseUrl)
            if (cookie.isNullOrBlank()) {
                Log.w(TAG, "Cookie session kosong untuk $baseUrl - user belum login di WebView")
                return@launch
            }
            Log.d(TAG, "Cookie session ada, laravel_session: ${cookie.contains("laravel_session")}")

            val registeredToken = prefs.getString(KEY_REGISTERED_TOKEN, null)
            val registeredCookieHash = prefs.getString(KEY_REGISTERED_COOKIE_HASH, null)
            val alreadyRegistered = registeredToken == token &&
                registeredCookieHash == cookie.hashCode().toString()
            if (alreadyRegistered) return@launch

            if (postToken(endpoint, token, cookie)) {
                Log.d(TAG, "Token berhasil didaftarkan ke backend")
                prefs.edit()
                    .putBoolean(KEY_REGISTERED, true)
                    .putString(KEY_REGISTERED_TOKEN, token)
                    .putString(KEY_REGISTERED_COOKIE_HASH, cookie.hashCode().toString())
                    .apply()
            }
        }
    }

    private fun postToken(endpoint: String, token: String, cookie: String): Boolean {
        return runCatching {
            val escapedToken = token.replace("\"", "\\\"")
            val body = "{\"token\":\"$escapedToken\",\"platform\":\"android\",\"device_name\":\"${Build.MODEL}\"}"

            val conn = URL(endpoint).openConnection() as HttpURLConnection
            conn.instanceFollowRedirects = false
            conn.requestMethod = "POST"
            conn.doOutput = true
            conn.setRequestProperty("Content-Type", "application/json")
            conn.setRequestProperty("Accept", "application/json")
            conn.setRequestProperty("Cookie", cookie)
            conn.outputStream.use { it.write(body.toByteArray()) }

            val code = conn.responseCode
            if (code in 200..299) {
                Log.d(TAG, "POST $endpoint -> HTTP $code")
            } else {
                val errorBody = conn.errorStream?.bufferedReader()?.use { it.readText() } ?: ""
                Log.w(TAG, "POST $endpoint -> HTTP $code, body: ${errorBody.take(200)}")
            }
            conn.disconnect()
            code in 200..299
        }.getOrElse { e ->
            Log.e(TAG, "POST token gagal: ${e.message}", e)
            false
        }
    }
}
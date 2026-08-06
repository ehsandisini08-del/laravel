package com.billnet.admin

import android.content.Context
import android.os.Build
import android.webkit.CookieManager
import com.google.android.gms.tasks.Tasks
import com.google.firebase.messaging.FirebaseMessaging
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import java.net.HttpURLConnection
import java.net.URL

object FcmTokenRegistrar {

    private const val PREFS = "fcm_registration"
    private const val KEY_REGISTERED = "registered"
    private const val KEY_REGISTERED_TOKEN = "registered_token"
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
            if (token.isNullOrBlank()) return@launch

            val cookie = CookieManager.getInstance().getCookie(baseUrl)
            if (cookie.isNullOrBlank()) return@launch

            val alreadyRegistered = prefs.getBoolean(KEY_REGISTERED, false) &&
                prefs.getString(KEY_REGISTERED_TOKEN, null) == token
            if (alreadyRegistered) return@launch

            if (postToken(endpoint, token, cookie)) {
                prefs.edit()
                    .putBoolean(KEY_REGISTERED, true)
                    .putString(KEY_REGISTERED_TOKEN, token)
                    .apply()
            }
        }
    }

    private fun postToken(endpoint: String, token: String, cookie: String): Boolean {
        return runCatching {
            val escapedToken = token.replace("\"", "\\\"")
            val body = "{\"token\":\"$escapedToken\",\"platform\":\"android\",\"device_name\":\"${Build.MODEL}\"}"

            val conn = URL(endpoint).openConnection() as HttpURLConnection
            conn.requestMethod = "POST"
            conn.doOutput = true
            conn.setRequestProperty("Content-Type", "application/json")
            conn.setRequestProperty("Accept", "application/json")
            conn.setRequestProperty("Cookie", cookie)
            conn.outputStream.use { it.write(body.toByteArray()) }

            val code = conn.responseCode
            conn.disconnect()
            code in 200..299
        }.getOrDefault(false)
    }
}
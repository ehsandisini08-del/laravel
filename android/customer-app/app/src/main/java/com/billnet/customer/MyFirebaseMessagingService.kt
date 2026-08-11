package com.billnet.customer

import android.app.NotificationChannel
import android.app.NotificationManager
import android.content.Context
import android.os.Build
import android.util.Log
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import com.google.firebase.messaging.FirebaseMessagingService
import com.google.firebase.messaging.RemoteMessage

class MyFirebaseMessagingService : FirebaseMessagingService() {

    companion object {
        private const val TAG = "FCM"
    }

    override fun onNewToken(token: String) {
        super.onNewToken(token)
        Log.d(TAG, "Token FCM baru dari Firebase: ${token.take(30)}...")
        getSharedPreferences("fcm_registration", Context.MODE_PRIVATE)
            .edit()
            .putString("fcm_token", token)
            .putBoolean("registered", false)
            .apply()
    }

    override fun onMessageReceived(message: RemoteMessage) {
        super.onMessageReceived(message)
        Log.d(TAG, "Pesan FCM diterima: ${message.notification?.title} / ${message.notification?.body}")
        message.notification?.let {
            showNotification(it.title ?: "Billnet", it.body ?: "")
        }
    }

    private fun showNotification(title: String, body: String) {
        createChannel()

        val notification = NotificationCompat.Builder(this, BillnetApplication.CHANNEL_ID)
            .setSmallIcon(android.R.drawable.ic_dialog_info)
            .setContentTitle(title)
            .setContentText(body)
            .setAutoCancel(true)
            .build()

        try {
            NotificationManagerCompat.from(this)
                .notify(System.currentTimeMillis().toInt(), notification)
            Log.d(TAG, "Notifikasi ditampilkan ke tray")
        } catch (e: Exception) {
            Log.e(TAG, "Gagal menampilkan notifikasi", e)
        }
    }

    private fun createChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val channel = NotificationChannel(
                BillnetApplication.CHANNEL_ID,
                "Notifikasi Billnet",
                NotificationManager.IMPORTANCE_DEFAULT,
            )
            (getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager)
                .createNotificationChannel(channel)
            Log.d(TAG, "Channel notifikasi dipastikan ada")
        }
    }
}
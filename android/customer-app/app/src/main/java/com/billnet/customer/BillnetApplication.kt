package com.billnet.customer

import android.app.Application
import android.app.NotificationChannel
import android.app.NotificationManager
import android.os.Build

class BillnetApplication : Application() {

    companion object {
        const val CHANNEL_ID = "billnet_customer"
    }

    override fun onCreate() {
        super.onCreate()
        createNotificationChannel()
    }

    private fun createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val channel = NotificationChannel(
                CHANNEL_ID,
                "Notifikasi Billnet",
                NotificationManager.IMPORTANCE_DEFAULT,
            )
            (getSystemService(NotificationManager::class.java))
                .createNotificationChannel(channel)
        }
    }
}

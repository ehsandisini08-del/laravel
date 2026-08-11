package com.billnet.admin

import android.Manifest
import android.annotation.SuppressLint
import android.app.Activity
import android.app.DownloadManager
import android.content.ActivityNotFoundException
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Bitmap
import android.graphics.Color
import android.net.Uri
import android.os.Build
import android.os.Bundle
import android.os.Environment
import android.view.View
import android.webkit.CookieManager
import android.webkit.GeolocationPermissions
import android.webkit.URLUtil
import android.webkit.ValueCallback
import android.webkit.WebChromeClient
import android.webkit.WebResourceError
import android.webkit.WebResourceRequest
import android.webkit.WebSettings
import android.webkit.WebView
import android.webkit.WebViewClient
import android.widget.Toast
import androidx.activity.OnBackPressedCallback
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import com.billnet.admin.databinding.ActivityMainBinding

class MainActivity : AppCompatActivity() {

    companion object {
        const val BASE_URL = "https://billing.labsaid.site"
        const val DEVICE_TOKEN_ENDPOINT = "$BASE_URL/mobile/admin/device-token"
    }

    private lateinit var binding: ActivityMainBinding
    private var isErrorPage = false

    private val exitAnchors = setOf(
        "$BASE_URL/dashboard",
        "$BASE_URL/login",
    )
    private var isNavigatingBack = false

    private val notificationPermission =
        registerForActivityResult(ActivityResultContracts.RequestPermission()) { }

    private var pendingGeoOrigin: String? = null
    private var pendingGeoCallback: GeolocationPermissions.Callback? = null

    private val locationPermission =
        registerForActivityResult(ActivityResultContracts.RequestPermission()) { granted ->
            val origin = pendingGeoOrigin
            val callback = pendingGeoCallback
            pendingGeoOrigin = null
            pendingGeoCallback = null
            if (granted && callback != null && origin != null) {
                callback.invoke(origin, true, false)
            } else {
                callback?.invoke(origin ?: "", false, false)
            }
        }

    private var fileChooserCallback: ValueCallback<Array<Uri>>? = null

    private val fileChooserLauncher =
        registerForActivityResult(ActivityResultContracts.StartActivityForResult()) { result ->
            val callback = fileChooserCallback
            fileChooserCallback = null
            if (result.resultCode == Activity.RESULT_OK && result.data != null) {
                callback?.onReceiveValue(
                    WebChromeClient.FileChooserParams.parseResult(result.resultCode, result.data),
                )
            } else {
                callback?.onReceiveValue(null)
            }
        }

    private var pendingDownloadUrl: String? = null
    private var pendingDownloadFileName: String? = null
    private var pendingDownloadMimeType: String? = null

    private val storagePermission =
        registerForActivityResult(ActivityResultContracts.RequestPermission()) { granted ->
            val url = pendingDownloadUrl
            val fileName = pendingDownloadFileName
            val mimeType = pendingDownloadMimeType
            pendingDownloadUrl = null
            pendingDownloadFileName = null
            pendingDownloadMimeType = null
            if (granted && url != null && fileName != null) {
                enqueueDownload(url, fileName, mimeType ?: "application/octet-stream")
            } else {
                Toast.makeText(this, "Izin penyimpanan ditolak, file tidak diunduh.", Toast.LENGTH_SHORT).show()
            }
        }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)

        window.statusBarColor = Color.parseColor("#2563EB")

        setupWebView()

        binding.retryButton.setOnClickListener {
            binding.offlineView.visibility = View.GONE
            binding.webView.reload()
        }

        onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
            override fun handleOnBackPressed() {
                if (isExitAnchor(binding.webView.url)) {
                    finish()
                } else if (binding.webView.canGoBack()) {
                    isNavigatingBack = true
                    binding.webView.goBack()
                } else {
                    finish()
                }
            }
        })

        requestNotificationPermission()

        if (savedInstanceState == null) {
            binding.webView.loadUrl(BASE_URL)
        }
    }

    private fun requestNotificationPermission() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU &&
            ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS) !=
            PackageManager.PERMISSION_GRANTED
        ) {
            notificationPermission.launch(Manifest.permission.POST_NOTIFICATIONS)
        }
    }

    override fun onSaveInstanceState(outState: Bundle) {
        super.onSaveInstanceState(outState)
        binding.webView.saveState(outState)
    }

    override fun onRestoreInstanceState(savedInstanceState: Bundle) {
        super.onRestoreInstanceState(savedInstanceState)
        binding.webView.restoreState(savedInstanceState)
    }

    @SuppressLint("SetJavaScriptEnabled")
    private fun setupWebView() {
        val webView = binding.webView
        val settings: WebSettings = webView.settings
        settings.javaScriptEnabled = true
        settings.domStorageEnabled = true
        settings.databaseEnabled = true
        settings.loadWithOverviewMode = true
        settings.useWideViewPort = true

        binding.swipeRefresh.isEnabled = false

        CookieManager.getInstance().setAcceptCookie(true)
        CookieManager.getInstance().setAcceptThirdPartyCookies(webView, true)

        webView.setDownloadListener { url, _, contentDisposition, mimetype, _ ->
            val fileName = URLUtil.guessFileName(url, contentDisposition, mimetype)
            downloadFile(url, fileName, mimetype ?: "application/octet-stream")
        }

        webView.webViewClient = object : WebViewClient() {
            override fun onPageStarted(view: WebView?, url: String?, favicon: Bitmap?) {
                super.onPageStarted(view, url, favicon)
                binding.progressBar.visibility = View.VISIBLE
                isErrorPage = false
            }

            override fun shouldOverrideUrlLoading(view: WebView?, request: WebResourceRequest?): Boolean {
                val url = request?.url?.toString() ?: return false
                if (isGoogleMapsUrl(url)) {
                    openExternal(url)
                    return true
                }
                return false
            }

            override fun onPageFinished(view: WebView?, url: String?) {
                super.onPageFinished(view, url)
                binding.progressBar.visibility = View.GONE
                if (!isErrorPage) {
                    binding.offlineView.visibility = View.GONE
                }
                if (isNavigatingBack) {
                    isNavigatingBack = false
                } else if (isExitAnchor(url)) {
                    view?.clearHistory()
                }
                FcmTokenRegistrar.tryRegister(
                    context = this@MainActivity,
                    endpoint = DEVICE_TOKEN_ENDPOINT,
                    baseUrl = BASE_URL,
                )
            }

            override fun onReceivedError(
                view: WebView?,
                request: WebResourceRequest?,
                error: WebResourceError?,
            ) {
                super.onReceivedError(view, request, error)
                if (request?.isForMainFrame == true) {
                    isErrorPage = true
                    binding.progressBar.visibility = View.GONE
                    binding.offlineView.visibility = View.VISIBLE
                }
            }
        }

        webView.webChromeClient = object : WebChromeClient() {
            override fun onShowFileChooser(
                webView: WebView?,
                filePathCallback: ValueCallback<Array<Uri>>?,
                fileChooserParams: FileChooserParams?,
            ): Boolean {
                fileChooserCallback?.onReceiveValue(null)
                fileChooserCallback = filePathCallback ?: return false
                val intent = fileChooserParams?.createIntent()
                    ?: Intent(Intent.ACTION_GET_CONTENT).apply {
                        addCategory(Intent.CATEGORY_OPENABLE)
                        type = "*/*"
                    }
                try {
                    fileChooserLauncher.launch(intent)
                } catch (e: Exception) {
                    fileChooserCallback = null
                    return false
                }
                return true
            }

            override fun onGeolocationPermissionsShowPrompt(
                origin: String?,
                callback: GeolocationPermissions.Callback?,
            ) {
                if (origin == null || callback == null) {
                    return
                }
                pendingGeoOrigin = origin
                pendingGeoCallback = callback
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M &&
                    ContextCompat.checkSelfPermission(
                        this@MainActivity,
                        Manifest.permission.ACCESS_FINE_LOCATION,
                    ) != PackageManager.PERMISSION_GRANTED
                ) {
                    locationPermission.launch(Manifest.permission.ACCESS_FINE_LOCATION)
                } else {
                    callback.invoke(origin, true, false)
                    pendingGeoOrigin = null
                    pendingGeoCallback = null
                }
            }
        }
    }

    private fun normalizedUrl(url: String?): String? {
        return url?.substringBefore('#')?.substringBefore('?')?.trimEnd('/')
    }

    private fun isExitAnchor(url: String?): Boolean {
        return normalizedUrl(url) in exitAnchors
    }

    private fun isGoogleMapsUrl(url: String): Boolean {
        val uri = Uri.parse(url)
        val host = uri.host?.lowercase() ?: return false
        return host == "maps.google.com" ||
            (host == "www.google.com" && (uri.path ?: "").startsWith("/maps"))
    }

    private fun downloadFile(url: String, fileName: String, mimeType: String) {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.Q &&
            ContextCompat.checkSelfPermission(this, Manifest.permission.WRITE_EXTERNAL_STORAGE) !=
            PackageManager.PERMISSION_GRANTED
        ) {
            pendingDownloadUrl = url
            pendingDownloadFileName = fileName
            pendingDownloadMimeType = mimeType
            storagePermission.launch(Manifest.permission.WRITE_EXTERNAL_STORAGE)
            return
        }
        enqueueDownload(url, fileName, mimeType)
    }

    private fun enqueueDownload(url: String, fileName: String, mimeType: String) {
        try {
            val request = DownloadManager.Request(Uri.parse(url))
                .setTitle(fileName)
                .setDescription("Mengunduh file dari aplikasi")
                .setMimeType(mimeType)
                .setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED)
                .setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, fileName)
            val manager = getSystemService(Context.DOWNLOAD_SERVICE) as DownloadManager
            manager.enqueue(request)
            Toast.makeText(this, "Mengunduh $fileName...", Toast.LENGTH_SHORT).show()
        } catch (e: Exception) {
            android.util.Log.e("MainActivity", "Download failed", e)
            Toast.makeText(this, "Gagal mengunduh file.", Toast.LENGTH_SHORT).show()
        }
    }

    private fun openExternal(url: String) {
        try {
            startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url)))
        } catch (e: ActivityNotFoundException) {
            android.util.Log.e("MainActivity", "No activity found to open $url", e)
        }
    }
}
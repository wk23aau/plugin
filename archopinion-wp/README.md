# Installation Instructions
1.  **Create the plugin directory**: Create a folder called `archopinion-wp` in your WordPress `wp-content/plugins/` directory.

2.  **Add all the PHP files**: Copy all the PHP files into their respective directories as shown in the structure.

3.  **Install TCPDF**: Use Composer to install TCPDF for PDF generation:
    ```bash
    cd wp-content/plugins/archopinion-wp
    composer require tecnickcom/tcpdf
    ```

4.  **Activate the plugin**: Go to WordPress admin > Plugins and activate "Archopinion - AI Architectural Review".

5.  **Configure settings**: Go to Archopinion > Settings and add your Gemini API key.

# Features
- **WordPress Native**: Fully integrated WordPress plugin
- **Multi-step Form**: User-friendly wizard interface
- **File Management**: Upload PDFs with drag-and-drop support
- **Background Processing**: Asynchronous analysis with progress tracking
- **PDF Reports**: Professional reports generated with TCPDF
- **Database Storage**: Analysis history stored in custom table
- **AJAX-powered**: Smooth user experience without page reloads
- **Responsive Design**: Works on all devices

The plugin is now ready to use! Users can access it through the WordPress admin menu to create new architectural analyses.

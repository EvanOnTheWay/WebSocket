# WebSocket


linux服务器需要添加

map $http_upgrade $connection_upgrade {
    default upgrade;
    '' close;
  }
  upstream ws{
    server 127.0.0.1:8080;
  } 

 server {
        listen       8090;
        server_name  localhost;
        root   html;
        index  index.html index.htm index.php;
        location / {
            proxy_pass  http://127.0.0.1:8080;
            #Proxy Settings
            #proxy_redirect     off;
            #proxy_set_header   Host             $host;
            #proxy_set_header   X-Real-IP        $remote_addr;
            #proxy_set_header   X-Forwarded-For  $proxy_add_x_forwarded_for;
            #proxy_next_upstream error timeout invalid_header http_500 http_502 http_503 http_504;
            #proxy_max_temp_file_size 0;
            #proxy_connect_timeout      90;
            #proxy_send_timeout         90;
            #proxy_read_timeout         90;
            #proxy_buffer_size          4k;
            #proxy_buffers              4 32k;
            #proxy_busy_buffers_size    64k;
            #proxy_temp_file_write_size 64k;
            proxy_http_version 1.1;
            proxy_set_header Connection "Keep-Alive";
        } 
    }

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Market Tracker</title>
    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.1.1/dist/chart.umd.min.js"></script>
    <!-- Include Luxon for date handling -->
    <script src="https://cdn.jsdelivr.net/npm/luxon@3.0.3/build/global/luxon.min.js"></script>
    <!-- Include Chart.js Luxon Date Adapter -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.3.0/dist/chartjs-adapter-luxon.umd.min.js"></script>
    <!-- Include Axios for HTTP requests -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .container {
            width: 80%;
            margin: 0 auto;
        }
        #chartContainer {
            width: 100%;
            height: 400px;
            margin-bottom: 20px;
        }
        #newsContainer {
            margin-top: 30px;
        }
        .newsItem {
            background: #fff;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .newsItem h3 {
            margin: 0 0 5px;
        }
        .newsItem a {
            color: #0073e6;
            text-decoration: none;
        }
        .newsItem a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>Stock Market Tracker for Google (GOOGL)</h1>
    <div class="container">
        <div id="chartContainer">
            <canvas id="stockChart"></canvas>
        </div>
        <div id="newsContainer">
            <h2>Latest News</h2>
            <div id="newsList"></div>
        </div>
    </div>

    <script>
        // Global variables
        let stockPrices = [];
        let timestamps = [];
        let newsHeadlines = [];

        // Configuration
        const STOCK_SYMBOL = 'GOOGL'; // Google stock symbol
        const PHP_API_URL = 'https://betahut.bounceme.net/stockmarketreader/api.php'; // Replace with your PHP file URL
		        const proxyUrl = 'https://cors-anywhere.herokuapp.com/'; // CORS proxy URL to bypass CORS restrictions

        // Initialize Chart
        const ctx = document.getElementById('stockChart').getContext('2d');
        const stockChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: timestamps,
                datasets: [{
                    label: `Stock Price of ${STOCK_SYMBOL}`,
                    data: stockPrices,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    fill: false
                }]
            },
            options: {
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'minute'
                        },
                        adapters: {
                            date: {
                                library: 'luxon',
                                config: {
                                    DateTime: luxon.DateTime,
                                    locale: 'en-US'
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Time'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Stock Price'
                        }
                    }
                }
            }
        });

        // Function to update the chart
        function updateChart() {
            stockChart.data.labels = timestamps;
            stockChart.data.datasets[0].data = stockPrices;
            stockChart.update();
        }

        // Function to fetch stock price using the PHP proxy
        // Function to fetch stock price with your CORS proxy
        async function fetchStockPrice() {
            try {
                const response = await axios.get(`${proxyUrl}https://query1.finance.yahoo.com/v8/finance/chart/${STOCK_SYMBOL}`);
                const stockData = response.data.chart.result[0];
                const currentPrice = stockData.meta.regularMarketPrice;
                const timestamp = new Date();

                stockPrices.push(currentPrice);
                timestamps.push(timestamp);

                console.log(`Time: ${timestamp}, ${STOCK_SYMBOL} Stock Price: ${currentPrice}`);
                updateChart();
            } catch (error) {
                console.error('Error fetching stock price:', error);
            }
        }


        // Function to fetch news headlines using the PHP proxy
       // Function to fetch news headlines using the PHP proxy
async function fetchNewsHeadlines() {
    try {
        // Make request to PHP API
        const response = await axios.get(`${PHP_API_URL}?type=news&query=Google stock market`);

        // Log the entire response to see its structure
        console.log('News API Response:', response);

        // Check if the response data contains the 'articles' property
        if (response.data && response.data.articles) {
            const articles = response.data.articles;

            // Clear previous news headlines
            document.getElementById('newsList').innerHTML = '';

            // Display each article
            articles.forEach(article => {
                const newsItem = document.createElement('div');
                newsItem.className = 'newsItem';
                newsItem.innerHTML = `<h3>${article.title}</h3><p>${article.description}</p><a href="${article.url}" target="_blank">Read more</a>`;
                document.getElementById('newsList').appendChild(newsItem);
            });
        } else {
            // Handle the case where 'articles' is not present
            console.error('Error fetching news headlines: "articles" property is undefined in response.');
            console.log('Response received:', response.data);
        }
    } catch (error) {
        // Catch and log any error that occurred during the API request
        console.error('Error fetching news headlines:', error);
    }
}


        // Function to update data every minute
        setInterval(() => {
            fetchStockPrice();
            fetchNewsHeadlines();
        }, 900000); // 60 seconds interval

        // Initial fetch
        fetchStockPrice();
        fetchNewsHeadlines();
    </script>
</body>
</html>

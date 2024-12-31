<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Market Tracker</title>
     <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }
        .search-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            gap: 10px;
        }
        #searchInput {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        #searchButton {
            padding: 10px 20px;
            background-color: #1976d2;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        #searchButton:hover {
            background-color: #1565c0;
        }
        #errorMessage {
            color: #d32f2f;
            margin-top: 10px;
            display: none;
        }
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
            font-style: italic;
            color: #666;
        }
        /* Previous styles remain the same */
        #keywordsContainer {
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .keyword-tag {
            display: inline-block;
            background-color: #e3f2fd;
            color: #1976d2;
            padding: 5px 10px;
            border-radius: 15px;
            margin: 5px;
            font-size: 14px;
        }
        #chartContainer {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .newsItem {
            background: #fff;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .timestamp {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 8px;
        }
        #symbolTitle {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Stock Market Tracker</h1>
        
        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Enter stock symbol (e.g., AAPL, GOOGL, MSFT)" />
            <button id="searchButton">Search</button>
            <div id="errorMessage"></div>
        </div>

        <h2 id="symbolTitle"></h2>
        
        <div id="keywordsContainer">
            <h2>Trending Keywords</h2>
            <div id="keywordsList"></div>
        </div>

        <div class="loading" id="loadingIndicator">Loading data...</div>

        <div id="chartContainer">
            <canvas id="stockChart"></canvas>
        </div>

        <div id="newsContainer">
            <h2>Latest News</h2>
            <div id="newsList"></div>
        </div>
    </div>

    <script>
        let currentSymbol = '';
        let stockChart;
        let updateInterval;

        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 5000);
        }

        function showLoading(show) {
            document.getElementById('loadingIndicator').style.display = show ? 'block' : 'none';
        }

        function updateSymbolTitle(symbol) {
            document.getElementById('symbolTitle').textContent = `Stock Data for ${symbol}`;
        }

        // Initialize Chart
        function initializeChart() {
            const ctx = document.getElementById('stockChart').getContext('2d');
            stockChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Stock Price',
                        data: [],
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: 'minute'
                            }
                        },
                        y: {
                            beginAtZero: false
                        }
                    }
                }
            });
        }

        // Function to extract and rank keywords
        function rankKeywords(articles) {
            const text = articles
                .map(article => `${article.title} ${article.description}`)
                .join(' ');
            
            const words = text.toLowerCase()
                .replace(/[^\w\s]/g, '')
                .split(/\s+/)
                .filter(word => word.length > 3)
                .filter(word => !['this', 'that', 'with', 'from', 'what', 'where', 'when'].includes(word));
            
            const wordCount = {};
            words.forEach(word => {
                wordCount[word] = (wordCount[word] || 0) + 1;
            });
            
            return Object.entries(wordCount)
                .sort(([,a], [,b]) => b - a)
                .slice(0, 10)
                .map(([word, count]) => ({ word, count }));
        }

        function formatTimestamp(timestamp) {
            return new Date(timestamp).toLocaleString();
        }

        function updateKeywords(keywords) {
            const keywordsList = document.getElementById('keywordsList');
            keywordsList.innerHTML = keywords
                .map(({ word, count }) => 
                    `<span class="keyword-tag">${word} (${count})</span>`)
                .join('');
        }

        function updateNews(articles) {
            const newsList = document.getElementById('newsList');
            newsList.innerHTML = articles
                .map(article => `
                    <div class="newsItem">
                        <div class="timestamp">${formatTimestamp(article.publishedAt)}</div>
                        <h3>${article.title}</h3>
                        <p>${article.description}</p>
                        <a href="${article.url}" target="_blank">Read more</a>
                    </div>`)
                .join('');
            
            const keywords = rankKeywords(articles);
            updateKeywords(keywords);
        }

        async function fetchData(type, params = {}) {
            showLoading(true);
            const queryString = new URLSearchParams({ type, ...params }).toString();
            try {
                const response = await fetch(`api.php?${queryString}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                if (data.error) {
                    throw new Error(data.error);
                }
                return data;
            } catch (error) {
                showError(`Error fetching ${type} data: ${error.message}`);
                return null;
            } finally {
                showLoading(false);
            }
        }

        async function fetchStockData(symbol) {
            const data = await fetchData('stock', { symbol });
            if (data && data.chart && data.chart.result) {
                const result = data.chart.result[0];
                const prices = result.indicators.quote[0].close;
                const timestamps = result.timestamp;

                stockChart.data.labels = timestamps.map(t => new Date(t * 1000));
                stockChart.data.datasets[0].data = prices.filter(price => price !== null);
                stockChart.data.datasets[0].label = `${symbol} Stock Price`;
                stockChart.update();
            }
        }

        async function fetchNews(symbol) {
    try {
        console.log('Fetching news for:', symbol);
        const data = await fetchData('news', { query: symbol });
        console.log('News data received:', data);
        
        if (data && data.articles) {
            console.log('Updating news with articles:', data.articles);
            updateNews(data.articles);
        } else {
            console.error('No articles found in news data');
            showError('No news articles available');
        }
    } catch (error) {
        console.error('Error in fetchNews:', error);
        showError('Failed to fetch news');
    }
}

function analyzeArticleContent(article) {
    // Extract words and their frequencies
    const text = `${article.title} ${article.description}`.toLowerCase();
    const words = text.match(/\b\w+\b/g) || [];
    
    // Count word frequencies
    const wordFrequencies = {};
    words.forEach(word => {
        if (word.length > 3) { // Only count words longer than 3 letters
            wordFrequencies[word] = (wordFrequencies[word] || 0) + 1;
        }
    });

    // Find definition-like sentences
    const sentences = article.description.split(/[.!?]+/).map(s => s.trim()).filter(s => s.length > 0);
    const definitions = sentences.filter(sentence => {
        // Look for common definition patterns
        return sentence.match(/\b(is|are|means|refers to|defined as|describes|represents)\b/i) &&
               !sentence.match(/\b(if|when|while|because)\b/i); // Exclude conditional sentences
    });

    return {
        frequencies: Object.entries(wordFrequencies)
            .sort(([,a], [,b]) => b - a)
            .slice(0, 10), // Top 10 most frequent words
        definitions: definitions
    };
}

function updateNews(articles) {
    try {
        console.log('Updating news with articles:', articles);
        const newsList = document.getElementById('newsList');
        if (!newsList) {
            console.error('News list element not found');
            return;
        }

        const newsHtml = articles.map(article => {
            const analysis = analyzeArticleContent(article);
            
            // Create the frequency table HTML
            const frequencyHtml = analysis.frequencies.map(([word, count]) => 
                `<span class="word-frequency">
                    <span class="word">${word}</span>
                    <span class="frequency">${count}</span>
                </span>`
            ).join('');

            // Create the definitions HTML
            const definitionsHtml = analysis.definitions.map(sentence => 
                `<div class="definition-sentence">${sentence}</div>`
            ).join('');

            return `
                <div class="newsItem">
                    <div class="timestamp">
                        ${formatTimestamp(article.publishedAt)}
                        <span class="source-tag">${article.source.name}</span>
                    </div>
                    <h3>${article.title}</h3>
                    <p>${article.description}</p>
                    
                    <div class="article-analysis">
                        <div class="word-frequencies">
                            <h4>Word Frequencies</h4>
                            <div class="frequency-cloud">
                                ${frequencyHtml}
                            </div>
                        </div>
                        
                        ${analysis.definitions.length > 0 ? `
                            <div class="definitions-found">
                                <h4>Possible Definitions Found</h4>
                                ${definitionsHtml}
                            </div>
                        ` : ''}
                    </div>
                    
                    <a href="${article.url}" target="_blank">Read more</a>
                </div>`;
        }).join('');

        console.log('Generated HTML:', newsHtml);
        newsList.innerHTML = newsHtml;

        // Add these styles dynamically
        const styleSheet = document.createElement("style");
        styleSheet.textContent = `
            .article-analysis {
                margin-top: 15px;
                padding: 15px;
                background: #f8f9fa;
                border-radius: 8px;
            }

            .word-frequencies h4,
            .definitions-found h4 {
                margin: 0 0 10px 0;
                color: #2c3e50;
                font-size: 1em;
            }

            .frequency-cloud {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin-bottom: 15px;
            }

            .word-frequency {
                background: #e3f2fd;
                padding: 4px 8px;
                border-radius: 15px;
                font-size: 0.9em;
                display: inline-flex;
                align-items: center;
                gap: 5px;
            }

            .word-frequency .word {
                color: #1976d2;
            }

            .word-frequency .frequency {
                background: #1976d2;
                color: white;
                padding: 2px 6px;
                border-radius: 10px;
                font-size: 0.8em;
            }

            .definition-sentence {
                background: white;
                padding: 10px;
                margin: 5px 0;
                border-left: 3px solid #4caf50;
                font-style: italic;
                color: #555;
            }
        `;
        document.head.appendChild(styleSheet);

        // Update keywords (keep existing functionality)
        if (articles.length > 0) {
            const keywords = rankKeywords(articles);
            updateKeywords(keywords);
        }
    } catch (error) {
        console.error('Error in updateNews:', error);
        showError('Failed to update news display');
    }
}

        async function searchSymbol(symbol) {
            if (!symbol) {
                showError('Please enter a stock symbol');
                return;
            }

            // Clear previous interval if it exists
            if (updateInterval) {
                clearInterval(updateInterval);
            }

            currentSymbol = symbol.toUpperCase();
            updateSymbolTitle(currentSymbol);

            // Fetch initial data
            await Promise.all([
                fetchStockData(currentSymbol),
                fetchNews(currentSymbol)
            ]);

            // Set up new interval for the current symbol
            updateInterval = setInterval(() => {
                fetchStockData(currentSymbol);
                fetchNews(currentSymbol);
            }, 900000); // 15 minutes
        }

        // Initialize and set up event listeners
        document.addEventListener('DOMContentLoaded', () => {
            initializeChart();

            const searchButton = document.getElementById('searchButton');
            const searchInput = document.getElementById('searchInput');

            searchButton.addEventListener('click', () => {
                searchSymbol(searchInput.value);
            });

            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    searchSymbol(searchInput.value);
                }
            });

            // Start with a default symbol (optional)
            searchSymbol('AAPL');
        });

    </script>
</body>
</html>
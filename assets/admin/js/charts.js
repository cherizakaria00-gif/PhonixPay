const PF_CHART_COLORS = ['#87c5a6', '#323444'];
const pfBuildPalette = (size) => Array.from({ length: size }, (_, index) => PF_CHART_COLORS[index % PF_CHART_COLORS.length]);

// doughnut
function piChart(element, labels, data) {

    new Chart(element, {
         type: 'doughnut',
         data: {
              labels: labels,
              datasets: [{
                   data: data,
                   backgroundColor: pfBuildPalette(labels.length),
                   borderColor: [
                        'rgba(50, 52, 68, 0.35)'
                   ],
                   borderWidth: 0,

              }]
         },
         options: {
              aspectRatio: 1,
              responsive: true,
              maintainAspectRatio: true,
              elements: {
                   line: {
                        tension: 0 // disables bezier curves
                   }
              },
              scales: {
                   xAxes: [{
                        display: false
                   }],
                   yAxes: [{
                        display: false
                   }]
              },
              legend: {
                   display: false,
              }
         }
    });
}

function barChart(element, currency, series, categories, height = 380) {
    const colors = PF_CHART_COLORS;

    let options = {
         series: series,
         chart: {
              type: 'bar',
              height: height,
              toolbar: {
                   show: true,
                   offsetX: 0,
                   offsetY: 0,
                   tools: {
                        download: true,
                        selection: true,
                        zoom: true,
                        zoomin: true,
                        zoomout: true,
                        pan: true,
                        reset: true,
                        customIcons: []
                   },
                   export: {
                        csv: {
                             filename: undefined,
                             columnDelimiter: ',',
                             headerCategory: 'category',
                             headerValue: 'value',
                             dateFormatter(timestamp) {
                                  return new Date(timestamp).toDateString()
                             }
                        },
                        svg: {
                             filename: undefined,
                        },
                        png: {
                             filename: undefined,
                        }
                   },
                   autoSelected: 'zoom'
              },
         },
         plotOptions: {
              bar: {
                   horizontal: false,
                   columnWidth: '50%',
                   endingShape: 'rounded'
              },
         },
         dataLabels: {
              enabled: false
         },
         stroke: {
              show: true,
              width: 2,
              colors: ['transparent']
         },
         xaxis: {
              categories: categories,
         },

         yaxis: {
              title: {
                   text: currency,
                   style: {
                        color: '#7c97bb'
                   }
              }
         },
         grid: {
              xaxis: {
                   lines: {
                        show: false
                   }
              },
              yaxis: {
                   lines: {
                        show: false
                   }
              },
         },
         fill: {
              opacity: 1,
              colors: colors
         },
         tooltip: {
              y: {
                   formatter: function (val) {
                        return currency + " " + val + " "
                   },
              },
              marker: {
                   fillColors: colors
              },
         },
         legend: {
              show: true,
              markers: {
                   fillColors: colors
              },
              labels: {
                   colors: colors
              }
         }
    };

    let chart = new ApexCharts(element, options);
    chart.render();
    return chart
}

function lineChart(element, series, categories, height = 380) {
    const colors = PF_CHART_COLORS;
    var options = {
         chart: {
              height: height,
              type: "area",
              toolbar: {
                   show: true,
                   offsetX: 0,
                   offsetY: 0,
                   tools: {
                        download: true,
                        selection: true,
                        zoom: true,
                        zoomin: true,
                        zoomout: true,
                        pan: true,
                        reset: true,
                        customIcons: []
                   },
                   autoSelected: 'zoom'
              },
              dropShadow: {
                   enabled: true,
                   enabledSeries: [0],
                   top: -2,
                   left: 0,
                   blur: 10,
                   opacity: 0.08
              },
              animations: {
                   enabled: true,
                   easing: 'linear',
                   dynamicAnimation: {
                        speed: 1000
                   }
              },
         },
         colors: colors,
         dataLabels: {
              enabled: false
         },
         series: series,
         fill: {
              type: "gradient",
              gradient: {
                   shadeIntensity: 1,
                   opacityFrom: 0.7,
                   opacityTo: 0.9,
                   stops: [0, 90, 100]
              }
         },
         xaxis: {
              categories: categories
         },
         grid: {
              padding: {
                   left: 5,
                   right: 5
              },
              xaxis: {
                   lines: {
                        show: false
                   }
              },
              yaxis: {
                   lines: {
                        show: false
                   }
              },
         },
    };

    var chart = new ApexCharts(element, options);

    chart.render();

    return chart
}

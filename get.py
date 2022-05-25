from bs4 import BeautifulSoup as bs
from urllib.request import urlopen
from urllib.parse import urlencode, quote_plus
import sys
import pprint
import html
import csv
import pandas as pd

csv_file = 'products_py.csv'
csv_content = [["name","rating","regular_price","sale_price","availability","imgs_src","description","categories","sku","amount"]]

url = 'https://speedgreens.co/product-category/sale-items/page/2'
#soup = bs(urlopen(url).read(), 'html.parser')

url = 'cache/' +  quote_plus(url)
soup = bs(open(url).read(), 'html.parser')

products = soup.find_all('li', {'class' : 'product-col'})

def process_array(arr):
	arr_p = []
	for elem in arr:
		if elem: 
			arr_p.append(html.unescape(elem.strip()))
		else:
			arr_p.append('')
	return arr_p

def process_url(url):
	global csv_content
	#soup = bs(urlopen(url).read(), 'html.parser')
	url = 'cache/' +  quote_plus(url)
	soup = bs(open(url).read(), 'html.parser')

	name = soup.find('h2', {'class' : 'product_title'}).text

	price = soup.find('p', {'class' : 'price'})
	if price.find('del'):
		print('Good')
		sale_price = price.find('del').text;
		regular_price = price.find('ins').text;
	else:
		print('Not on sale, skipping')
		return

	availability = soup.find('span', {'class' : 'product-stock'})
	if availability:
		availability = availability.text.replace("Availability: ", "")

	categories = soup.find('span', {'class' : 'posted_in'})
	categories = categories.text.replace("Categories: ", "")

	rating = soup.find('strong', {'class' : 'rating'}).text
	description = str( soup.find('div', {'class' : 'description'}) ).replace('\n', '')
#	description = ''
	sku = soup.find('span', {'class' : 'sku_wrapper'})
	sku = sku.text.replace("SKU: ", "")	
	amount = soup.find('ul', {'class' : 'filter-item-list'})
	if amount:
		amount = soup.find('ul', {'class' : 'filter-item-list'}).text	

	imgs = soup.find_all('div', {'class' : 'img-thumbnail'})
	imgs_src = ''
	for img in imgs:
		img = img.find('img')
		if img.get('width') and int(img.get('width')) > 200 :#hardcoded
			img_src = img.get('data-lazy-src');
			imgs_src += ',' + img_src;

	row = [name, rating, regular_price, sale_price, availability, imgs_src, description, categories, sku, amount]
	row = process_array(row)
	csv_content.append(row)

for product in products:
	url = product.find('a', {'class' : 'product-loop-title'}).get('href')
	process_url(url)


pp = pprint.PrettyPrinter(indent=2)
pp.pprint(csv_content)

with open(csv_file, 'w', newline='\n') as file:
    mywriter = csv.writer(file, delimiter=',')
    mywriter.writerows(csv_content)

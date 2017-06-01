#!/usr/bin/sage

# Regular expressions
import re

# Getting command line arguments, exiting script
import sys

# Used when checking whether computation was done before
import os.path

# Used when copying pickled data for default polynomial
import shutil

# Storing Sage / Python objects as strings for use later
import pickle


# GET COMMAND LINE ARGUMENTS
# -------------------------------------

# Get a string representation of the polynomial coefficients
original_coeffs_string = sys.argv[1]

# Get the index of the prime number in Z that we're starting to factor at
starting_index = int(sys.argv[2])

# Get a string that (should) uniquely identify the current thread of computations
thread_id = sys.argv[3]




# GET NUMBER FIELD
# -------------------------------------

# Turn string representation of coefficients into an array
# We need to leave off empty string at the beginning of the array,
# see http://stackoverflow.com/q/2197451
coeffs_string = re.sub("n", "-", original_coeffs_string)
coeffs_array = coeffs_string.split("c")[1:]

# Turn array into a string representation of the polynomial
degree = len(coeffs_array) - 1
poly_string = ""
for i in range(len(coeffs_array)):
    poly_string += '(' + coeffs_array[i] + ') * x^(' + str(degree - i) + ')'
    if i < degree:
        poly_string += " + "

# Define R = Q[x], convert string representation of the polynomial into an element of Q[x]
R.<x> = PolynomialRing(QQ)
poly = R(poly_string)

# Define F = Q(a) where a is a root of poly, as long as it's monic and irreducible
if (poly.is_monic()):
    if (poly.is_irreducible()):
        F.<alpha> = NumberField(poly)
    else:
        print 'Oops! That isn\'t an <a target="_blank" href="http://en.wikipedia.org/wiki/Irreducible_polynomial">irreducible polynomial</a>'
        sys.exit()
else:
    print 'Oops! Sage needs a <a target="_blank" href="http://en.wikipedia.org/wiki/Monic_polynomial">monic polynomial</a><br>'
    sys.exit()



# RETRIEVE (OR CREATE) PICKLED DATA
# -------------------------------------

filename = 'pickled/' + original_coeffs_string + '-' + thread_id + '-' + str(starting_index)
new_filename = 'pickled/' + original_coeffs_string + '-' + thread_id + '-' + str(starting_index + 100)

# If someone has just opened the page, copy the pickled data for the
# default polynomial instead of recomputing it
if coeffs_array == [1,-5,6,2,4,1,-3,-2,1] and starting_index == 0:
    shutil.copy('pickled/default', filename)

if os.path.isfile(filename):
    pickled_data = open(filename, 'r')
    F._pari_nf = pickle.loads(pickled_data.read())
    pickled_data.close()
else:
    pickled_data = open(filename, 'w')

    # By calling F.pari_nf(), the information will be stored in the Sage object F
    # for the duration of this script call, as well as stored in the pickled file
    pickled_data.write(pickle.dumps(F.pari_nf()))
    pickled_data.close()

# Rename pickled file to update the value of "starting index" to the value
# that will be expected if/when this thread continues
os.rename(filename, new_filename)




# FACTOR PRIMES
# -------------------------------------

Z_prime = Primes().unrank(starting_index)

for i in range(starting_index, starting_index + 100):

    # Compute the factorization of Z_prime in O_F, collect ramification and inertia info
    I = F.ideal(Z_prime)
    OF_factors = I.prime_factors()

    # See http://stackoverflow.com/q/4233476
    OF_factors = sorted(OF_factors, key = lambda p: (-p.residue_class_degree(), p.ramification_index()))

    r = len(OF_factors)
    e = [p.ramification_index() for p in OF_factors]
    f = [p.residue_class_degree() for p in OF_factors]

    # Print the results in the format that the Javascript on the page is expecting
    print '<div id="factorization'  + str(i) + '">'
    print '  <div id="Z_prime'      + str(i) + '">' + str(Z_prime) + '</div>'
    print '  <div id="ramification' + str(i) + '">' + "-".join([str(n) for n in e]) + '</div>'
    print '  <div id="inertia'      + str(i) + '">' + "-".join([str(n) for n in f]) + '</div>'
    print '  <div id="latex'        + str(i) + '">'

    # Print pO_F = p_1^{e_1} ... p_r^{e_r}
    print '    <p>\[' + str(Z_prime) + '\mathcal{O}_F =',
    for j in range(r):
        print '\mathfrak{p}_{' + str(j+1) + '}',
        # replace with ternary?
        if(e[j] > 1):
            print '^{' + str(e[j]) + '}',
    print '\]</p>\n'

    # Print |O_F / p_i| = p^{f_i}
    print '    <p>\[\\begin{align*}'
    for j in range(r):
        print '        |\mathcal{O}_F/\mathfrak{p}_{' + str(j+1) + '}| &= ' + str(Z_prime),
        # replace with ternary?
        if(f[j] > 1):
            print '^{' + str(f[j]) + '}',
        if (j != r-1):
            print r"\\"
    print '\n        \end{align*}\]</p>'

    # Print p_i = (p, ...)
    print '    <p>\[\\begin{align*}'
    for j in range(r):
        explicit = str(latex(OF_factors[j]))
        explicit = re.sub("left", "bigl", explicit)
        explicit = re.sub("right", "bigr", explicit)
        print '         \mathfrak{p}_{' + str(j+1) + '}=\,&\\textstyle' + explicit
        if (j != r-1):
            print r"\\"
    print '\n        \end{align*}\]</p>'

    print '  </div>'
    print '</div>'

    # Increment Z_prime
    Z_prime = Primes().next(Z_prime)

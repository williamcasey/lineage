# lineage
Algorithm for determining the genealogical relationship (consanguinity) between two members in a family tree, all contained within a simple PHP class.

## The algorithm

This algorithm was created with one purpose in mind: to find the relation between one person to another person within a family tree. For example, if _person a_ was born two generations before _person b_ in a family tree, and _person a_ is _person b's_ direct ancestor, then _person a_ is _person b's_ grandparent. I have a working example of this algorithm with my own family [here](http://tree.wccasey.com/). When designing this algorithm, I followed the relationships outlined in this table of consanguinity from [Wikipedia](https://en.wikipedia.org/wiki/Consanguinity):

<p align="center"><img src="https://upload.wikimedia.org/wikipedia/commons/0/0d/Table_of_Consanguinity_showing_degrees_of_relationship.svg" width="550"/></p>

### Preface

In it's simplest form, this algorithm's dataset consists of members that make up a family tree, each with a unique id and the id of their parent. The family tree needs to have a root ancestor, which the tree will stem down from. The root ancestor needs to have an id of 1 and it's parent id is to be set to 0, as it has no parent within the dataset. In any practical implementation, it would be wise to give each member a name, like below. An example dataset with 6 members:

<table>
<tr><td><b>Name</b></td><td><b>ID</b></td><td><b>Parent ID</b></td></tr>
<tr><td>John Doe (root)</td><td>1</td><td>0</td></tr>
<tr><td>Mary Doe</td><td>2</td><td>1</td></tr>
<tr><td>Jane Doe</td><td>3</td><td>1</td></tr>
<tr><td>Bob Doe</td><td>4</td><td>1</td></tr>
<tr><td>Robert Doe</td><td>5</td><td>2</td></tr>
<tr><td>Betty Doe</td><td>6</td><td>4</td></tr>
</table>

<b>Tree Hierarchy</b><br />
John Doe - 1<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Mary Doe - 2<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Robert Doe - 5<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Jane Doe - 3<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Bob Doe - 4<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Betty Doe - 6<br />


In the above example, there are 3 generations, with John Doe having 3 children and 2 grandchildren. Generation 1 always has only one member, the root ancestor. All of the root ancestor's children are in generation 2, the root ancestor's grandchildren are in generation 3, and so on.

Along with the main algorithm, there are 4 "sub" algorithms to simplify the process.

### Variables and Functions

General Definitions:

*   **Tree**

    The tree, or family tree, is the dataset with the need information for the algorithm to work, and makes up a directed graph with nodes pointing to their parents.

*   **Member**

    A member is a node, or vertex, within the tree. One node makes up one person.

*   **Root Ancestor**

    The member in the tree from which the rest of the tree stems from.

The following are the dataset definitions, or the information each node needs to have and others information that can be added. Necessary fields are underlines:

*   _**ID**_

    Each member needs to have a unique ID. The root ancestor always needs to have their ID set to 1.

*   _**Parent ID**_

    Each member also needs to have a Parent ID, which makes up the hierarchy within the tree. The root ancestor's Parent ID needs to be set to 0, as they have no parent in the tree.

*   **Name**

    Though technically not required, in any practical use of this algorithm, you will want to supply each member a name.

*   **Gender/Sex**

    This is useful to have, as it allows you to use more specific pronouns, e.g. "mother" instead of "parent", "brother" vs. "sibling", or "aunt" vs. "aunt/uncle". The way I did it was **0** for male, **1** for female, and **2** for unspecified.

*   **Spouse**

    If member is married, this can also be useful to add, and if not just set it to NULL. In a more advanced implementation, I had a separate table in the database just for spouses and also included divorce history. It is also worth noting that this algorithm doesn't take incest or inbreeding into account.

*   **Birth/Death**

    With this, you can indicate if someone is alive or dead, and their age/lifespan. If in a database, these can be kept as a date. If member is still alive, then death can be set to NULL.

*   **Other**

    In my more advanced implementation, I split up the parts of the name into title, first-name, nickname, middle-name, and last-name, for more control over displaying the names. There are many other fields you could add too, such as birthplace and information about that person.

Algorithm Terms and Variables:

*   **_a_** and **_b_**

    The two input variables. **a** and **b** are both the ID of members in the tree. The algorithm finds **a's** relationship to **b**.

*   **_P_(_n_)**

    The parent of the nth member of the tree.

*   **_deg_**

    If a and b are cousins, then **deg**, or the degree of the cousins, is calculated. It is then put into cousin_prefixes below to determine the suffix.

*   **_cousin_prefixes_[]**

    An array, or at least a group, for the degree a cousin is, e.g "third cousin" or "first cousin". The first key in the array should be set to 1 ideally instead of 0, or subtract the degree by 1\. Values: First, Second, Third, Fourth, Fifth, Sixth, Seventh, Eighth, & Ninth.

*   **_cousin_suffixes_[]**

    An array, or at least a group, of suffixes to denote how many times removed two cousins are, e.g. "first cousin twice removed" or "Second Cousin 5 times removed". The first key in the array should be set to 1 ideally instead of 0, or subtract the generation difference by 1\. Values: Once, Twice, Thrice, 4 times, 5 times, 6 times, 7 times, 8 times, & 9 times.

"Sub" Algorithms/Functions Used In Algorithm (Explained in detail below):

*   gen() - Find generation
*   gendif() - Finds number of generations between two members
*   common() - Finds lowest common ancestor of two members
*   gen_prefix() - Determines how many "greats" to use
*   gen_anc() - Finds a member's ancestor from a certain generation

### "Sub" Algorithms/Functions Explained

The "sub" algorithms are as follows:

*   **Generation**

    Returns the generation of the given member in the tree.

    int **gen**(int **a**):

    1.  input **a**

        **a** is the ID of any given member in the dataset

    2.  int **gen** = **1**
    3.  if **a** == **1** then goto **7**
    4.  **a** = **P**(**a**)

        Go up one generation at a time until **a** is the root ancestor

    5.  **gen** = **gen** + **1**

        Every time **a** goes up a generation increment **gen**

    6.  goto **3**
    7.  return **gen**

*   **Generation Difference**

    Returns the number of generations between 2 members in the tree.

    int **gendif**(int **a**, int **b**):

    1.  input **a** and **b**

        **a** is the ID of any given member in the dataset
        **b** is the ID of any given member in the dataset

    2.  int **dif** = **gen**(**a**) - **gen**(**b**)
    3.  **dif** = **abs**(**dif**)

        [absolute value](https://en.wikipedia.org/wiki/Absolute_value) of **dif**

    4.  return **dif**

*   **Common Ancestor**

    Returns the [lowest common ancestor](https://en.wikipedia.org/wiki/Lowest_common_ancestor) of two members in the tree. If one of the members is a direct ancestor of the other member, that member is considered the common ancestor of the two.

    int **common**(int **a**, int **b**):

    1.  input **a** and **b**

        **a** is the ID of any given member in the dataset
        **b** is the ID of any given member in the dataset

    2.  if **a** == **b** then return **0**
    3.  int **gen_a** = **gen**(**a**) and int **gen_b** = **gen**(**b**)
    4.  if **a** == **b** then goto **20**
    5.  if **gen_a** < **gen_b** then continue else goto **10**
    6.  **b** = **P**(**b**)
    7.  **gen_b** = **gen_b** - **1**
    8.  if **a** == **1** or **b** == **1** then return **1**
    9.  goto **4**
    10.  if **gen_a** > **gen_b** then continue else goto **15**
    11.  **a** = **P**(**a**)
    12.  **gen_a** = **gen_a** - **1**
    13.  if **a** == **1** or **b** == **1** then return **1**
    14.  goto **4**
    15.  if **gen_a** == **gen_b** then continue
    16.  **a** = **P**(**a**)
    17.  **b** = **P**(**b**)
    18.  if **a** == **1** or **b** == **1** then return **1**
    19.  goto **4**
    20.  return **a**

*   **Generation Prefix**

    Generates the proper prefix based on generation difference if one member is a direct descendant or ancestor of the other member to go in front of "parent" or "child". For example if there is a 1 generation difference, then no prefix would be added, if there were two generations then "grand" would added, and if more than two generation difference, then gendif() * "great" would be added.

    string **gen_prefix**(int **gd**):

    1.  input **gd**

        **gd** is the gendif() between two members of the tree

    2.  if **gd** == **1** then return **""**
    3.  if **gd** == **2** then return **"grand"**
    4.  elseif **gd** > **2** then string **pre** = **"grand"**
    5.  for(int **i** = **1**; **i** <= **gd** - **2**; **i**++)
        **pre** = **"great "** + **pre**
    6.  return **pre**

*   **Ancestor From Generation**

    This function finds the ancestor of a member from a certain generation. For example, if you wanted to find a member's grandparent, you would input that member as **a** and the generation as **gen**(**a**) - **2**.

    int **gen_anc**(int **a**, int **g**):

    1.  input **a** and **g**

        **a** is the ID of any member in the tree
        **g** is the generation of the unknown ancestor of **a** to be found

    2.  if **gen**(**a**) <= **g** then goto **4**
    3.  **a** = **P**(**a**)
    4.  goto **1**
    5.  return **a**

### The Algorithm

The main algorithm returns a string containing the relation between **a** and **b**. Alternatively, it can also work and return a number instead of a string indicating the relation, such as 0 for direct ancestor, 1 for sibling, 2 for aunt/uncle, and 3 for cousin. With this output you could then put it into another function to convert the numerical representation into a string. If you choose this route, then you will also have to supply the generation difference, and for cousins, you will have to supply the degree. Including the sex of the member in the dataset can also be useful for using specific pronouns, e.g. "mother" instead of "parent" or "nephew" instead of "nephew/niece". This algorithm doesn't take situations like incest and inbreeding into account for simplicity.

string **relation**(int **a**, int **b**):

1.  input **a** and **b**

    **a** is the ID of any given member in the dataset
    **b** is the ID of any given member in the dataset

2.  if **P**(**a**) == **P**(**b**) then return **"sibling"**
3.  if **a** == **common**(**a**, **b**) then return **gen_prefix**(**gendif**(**a**, **b** )) + **"parent"**
4.  if **b** == **common**(**a**, **b**) then return **gen_prefix**(**gendif**(**a**, **b** )) + **"child"**
5.  if **P**(**a**) == **P**( **gen_anc**( **b**, **gen**( **a** ) ) then return **gen_prefix**(**gendif**(**a**, **b**)) + **"aunt/uncle"**
6.  if **P**(**b**) == **P**( **gen_anc**( **a**, **gen**( **b** ) ) then return **gen_prefix**(**gendif**(**a**, **b**)) + **"nephew/niece"**
7.  if **2** <= **gen**(**a**) - **gen**(**common**(**a**, **b**)
    1.  if **gen**(**a**) <= **gen**(**b**) then continue else goto **7.4**
    2.  int **deg** = (**gen**(**a**) - **gen**(**common**(**a**, **b**)) - **1**
    3.  goto **8**
    4.  int **deg** = (**gen**(**b**) - **gen**(**common**(**a**, **b**)) - **1**
    5.  goto **8**
8.  return **cousin_prefixes**[**deg**] + " cousin " + **cousin_suffixes**[**gendif**(**a**, **b**)]
